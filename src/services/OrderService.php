<?php

namespace batchnz\hubspotecommercebridge\services;

use batchnz\hubspotecommercebridge\enums\HubSpotObjectTypes;
use batchnz\hubspotecommercebridge\exceptions\CraftCommerceObjectMissing;
use batchnz\hubspotecommercebridge\exceptions\HubspotCommerceSchemaMissingException;
use batchnz\hubspotecommercebridge\exceptions\ProcessingSettingsException;
use batchnz\hubspotecommercebridge\models\HubspotOrder;
use batchnz\hubspotecommercebridge\models\OrderSettings;
use batchnz\hubspotecommercebridge\Plugin;
use batchnz\hubspotecommercebridge\records\HubspotCommerceObject;
use craft\base\Component;
use craft\commerce\elements\Order;
use HubSpot\Client\Crm\Deals\ApiException;
use HubSpot\Client\Crm\Deals\Model\Filter;
use HubSpot\Client\Crm\Deals\Model\FilterGroup;
use HubSpot\Client\Crm\Deals\Model\PublicObjectSearchRequest;
use HubSpot\Client\Crm\Deals\Model\SimplePublicObjectInput;
use HubSpot\Client\Crm\LineItems\Model\BatchInputSimplePublicObjectId;
use HubSpot\Client\Crm\LineItems\Model\SimplePublicObjectId;
use Hubspot\Discovery\Discovery as HubSpotApi;

/**
 * Class OrderService
 * @package batchnz\hubspotecommercebridge\services
 *
 * Handles all of the logic to do with importing Orders from Craft Commerce to the HubSpot store
 */
class OrderService extends Component implements HubspotServiceInterface
{
    private HubSpotApi $hubspot;
    private OrderSettings $settings;

    /**
     * @throws ProcessingSettingsException
     * @throws HubspotCommerceSchemaMissingException
     */
    public function __construct($config = [])
    {
        parent::__construct($config);
        $this->hubspot = Plugin::getInstance()->getHubSpot();
        $dealSchema = HubspotCommerceObject::findOne(['objectType' => HubSpotObjectTypes::DEAL]);
        if (!$dealSchema) {
            throw new HubspotCommerceSchemaMissingException('The DEAL schema is missing from the database.');
        }
        try {
            $orderSettings = OrderSettings::fromHubspotObject($dealSchema);
        } catch (\Exception $e) {
            throw new ProcessingSettingsException('Failed to process the settings for PRODUCT.');
        }
        $orderSettings->validate();
        $this->settings = $orderSettings;
    }

    /**
     * Fetches an order with it's associated order ID and returns it in
     * an object with only the attributes required by Hubspot
     * @param int $id Order ID
     * @return HubspotOrder
     * @throws CraftCommerceObjectMissing
     */
    public function fetch(int $id): HubspotOrder
    {
        $variant = Order::findOne(['id' => $id]);
        if (!$variant) {
            throw new CraftCommerceObjectMissing('Could not fetch Order with ID: ' . $id);
        }
        return HubspotOrder::fromCraftModel($variant);
    }

    /**
     * Maps properties to a format suitable to be included in the request to Hubspot
     * @param HubspotOrder $model
     */
    public function mapProperties($model): array
    {
        $properties = [];

        foreach ($this->settings->getArrayKeys() as $key) {
            $properties[$this->settings[$key]] = $model[$key];
        }

        return $properties;
    }

    /**
     * Finds a deal in hubspot using its unique key
     *
     * @param HubspotOrder $model
     * @return int|false
     */
    public function findInHubspot($model): string|false
    {
        $filter = new Filter([
            'property_name' => $this->settings[$this->settings->uniqueKey()],
            'value' => $model[$this->settings->uniqueKey()],
            'operator' => 'EQ',
        ]);
        $filterGroup = new FilterGroup([
            'filters' => [$filter],
        ]);
        $searchReq = new PublicObjectSearchRequest([
            'filter_groups' => [$filterGroup],
        ]);

        try {
            $res = $this->hubspot->crm()->deals()->searchApi()->doSearch($searchReq);
            return count($res->getResults()) ? $res->getResults()[0]->getId() : false;
        } catch (ApiException $e) {
            return false;
        }
    }

    /**
     * Creates a deal in Hubspot. If the deal already exists, then updates the existing deal.
     * @param HubspotOrder $model
     */
    public function upsertToHubspot($model): string|false
    {
        $properties = $this->mapProperties($model);
        $existingObjectId = $this->findInHubspot($model);
        $dealInput = new SimplePublicObjectInput();
        try {
            if ($existingObjectId) {
                // Don't upsert the unique key
                unset($properties[$this->settings[$this->settings->uniqueKey()]]);
                $dealInput->setProperties($properties);
                $res = $this->hubspot->crm()->deals()->basicApi()->update($existingObjectId, $dealInput);
            } else {
                $dealInput->setProperties($properties);
                $res = $this->hubspot->crm()->deals()->basicApi()->create($dealInput);
            }
            return $res->getId();
        } catch (ApiException $e) {
            return false;
        }
    }

    /**
     * Deletes a deal from Hubspot.
     *
     * @param HubspotOrder $model
     * @return int|false
     */
    public function deleteFromHubspot($model): int|false
    {
        $existingObjectId = $this->findInHubspot($model);
        if (!$existingObjectId) {
            return false;
        }
        try {
            $this->hubspot->crm()->deals()->basicApi()->archive($existingObjectId);
            return $existingObjectId;
        } catch (ApiException $e) {
            return false;
        }
    }

    /**
     * Deletes line items from a Deal in Hubspot. This deletes with in batches with a max limit of 100;
     */
    public function deleteLineItemsFromHubspot(int $dealId): void
    {
        $res = $this->hubspot->apiRequest([
            'method' => 'GET',
            'path' => "/crm/v3/objects/deals/{$dealId}/associations/line_items"
        ]);

        $resArray = json_decode($res->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $lineItemIds = array_map(static fn ($result) => new SimplePublicObjectId(['id' => $result['id']]), $resArray['results']);
        $batchLineItems = new BatchInputSimplePublicObjectId([
            'inputs' => $lineItemIds,
        ]);
        $this->hubspot->crm()->lineItems()->batchApi()->archive($batchLineItems);
    }
}
