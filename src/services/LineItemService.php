<?php

namespace batchnz\hubspotecommercebridge\services;

use batchnz\hubspotecommercebridge\enums\HubSpotObjectTypes;
use batchnz\hubspotecommercebridge\models\HubspotLineItem;
use batchnz\hubspotecommercebridge\models\LineItemSettings;
use batchnz\hubspotecommercebridge\Plugin;
use batchnz\hubspotecommercebridge\records\HubspotCommerceObject;
use craft\base\Component;
use craft\commerce\records\LineItem;
use CraftCommerceObjectMissing;
use HubSpot\Client\Crm\LineItems\ApiException;
use HubSpot\Client\Crm\LineItems\Model\SimplePublicObjectInput;
use HubSpot\Crm\ObjectType;
use HubspotCommerceSchemaMissingException;
use Hubspot\Discovery\Discovery as HubSpotApi;
use ProcessingSettingsException;

/**
 * Class LineItemService
 * @package batchnz\hubspotecommercebridge\services
 *
 * Handles all of the logic to do with importing LineItems from Craft Commerce to the HubSpot store
 */
class LineItemService extends Component implements HubspotServiceInterface
{
    private HubSpotApi $hubspot;
    private LineItemSettings $settings;

    /**
     * @throws ProcessingSettingsException
     * @throws HubspotCommerceSchemaMissingException
     */
    public function __construct($config = [])
    {
        parent::__construct($config);
        $this->hubspot = Plugin::getInstance()->getHubSpot();
        $lineItemSchema = HubspotCommerceObject::findOne(['objectType' => HubSpotObjectTypes::LINE_ITEM]);
        if (!$lineItemSchema) {
            throw new HubspotCommerceSchemaMissingException('The LINE_ITEM schema is missing from the database.');
        }
        try {
            $lineItemSettings = LineItemSettings::fromHubspotObject($lineItemSchema);
        } catch (\Exception $e) {
            throw new ProcessingSettingsException('Failed to process the settings for PRODUCT.');
        }
        $lineItemSettings->validate();
        $this->settings = $lineItemSettings;
    }

    /**
     * Fetches a line item with it's associated line item ID and returns it in
     * an object with only the attributes required by Hubspot
     * @param int $id LineItem ID
     * @return HubspotLineItem
     * @throws CraftCommerceObjectMissing
     */
    public function fetch(int $id): HubspotLineItem
    {
        $lineItem = LineItem::findOne(['id' => $id]);
        if (!$lineItem) {
            throw new CraftCommerceObjectMissing('Could not fetch LineItem with ID: ' . $id);
        }
        return HubspotLineItem::fromCraftModel($lineItem);
    }

    /**
     * Maps properties to a format suitable to be included in the request to Hubspot
     * @param HubspotLineItem $model
     */
    public function mapProperties($model): array
    {
        $properties = [];

        foreach (array_keys($this->settings->attributes) as $key) {
            $properties[$this->settings[$key]] = $model[$key];
        }

        return $properties;
    }

    /**
     * Finds a line item in hubspot using its unique key
     *
     * @param HubspotLineItem $model
     * @return int|false
     */
    public function findInHubspot($model): string|false
    {
        // TODO: Define this function if it is ever needed to find line items in Hubspot
        return false;
    }

    /**
     * Creates a line item in Hubspot. If the line item already exists, then line item the existing deal.
     * @param HubspotLineItem $model
     */
    public function upsertToHubspot($model): string|false
    {
        $properties = $this->mapProperties($model);
        $lineItemInput = new SimplePublicObjectInput();
        $lineItemInput->setProperties($properties);
        try {
            return $this->hubspot->crm()->lineItems()->basicApi()->create($lineItemInput)->getId();
        } catch (ApiException $e) {
            return false;
        }
    }

    /**
     * Deletes a line item from Hubspot.
     *
     * @param HubspotLineItem $model
     * @return int|false
     */
    public function deleteFromHubspot($model): int|false
    {
        // TODO: Implement deleteFromHubspot() method.
        return false;
    }

    /**
     * Associates a LineItem with a Deal in Hubspot
     * @throws ApiException
     */
    public function associateToDeal(int $hubspotLineItemId, $hubspotDealId): void
    {
        $this->hubspot
            ->crm()
            ->lineItems()
            ->associationsApi()
            ->create($hubspotLineItemId, ObjectType::DEALS, $hubspotDealId, 'line_item_to_deal');
    }
}
