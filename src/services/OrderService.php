<?php

namespace batchnz\hubspotecommercebridge\services;

use batchnz\hubspotecommercebridge\enums\HubSpotObjectTypes;
use batchnz\hubspotecommercebridge\models\HubspotOrder;
use batchnz\hubspotecommercebridge\models\OrderSettings;
use batchnz\hubspotecommercebridge\Plugin;
use batchnz\hubspotecommercebridge\records\HubspotCommerceObject;
use Craft;
use craft\base\Component;
use craft\commerce\elements\Order;
use CraftCommerceObjectMissing;
use HubspotCommerceSchemaMissingException;
use JsonException;
use SevenShores\Hubspot\Exceptions\BadRequest;
use SevenShores\Hubspot\Factory;
use SevenShores\Hubspot\Resources\CrmAssociations;
use yii\base\Exception;

/**
 * Class OrderService
 * @package batchnz\hubspotecommercebridge\services
 *
 * Handles all of the logic to do with importing Orders from Craft Commerce to the HubSpot store
 */
class OrderService extends Component implements HubspotServiceInterface
{
    public const MAX_LIMIT = 100;

    private Factory $hubspot;

    public function __construct($config = [])
    {
        parent::__construct($config);
        $this->hubspot = Plugin::getInstance()->getHubSpot();
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
     *
     * @throws Exception
     * @throws JsonException
     * @throws HubspotCommerceSchemaMissingException
     */
    public function mapProperties($model): array
    {
        $dealSchema = HubspotCommerceObject::findOne(['objectType' => HubSpotObjectTypes::DEAL]);
        if (!$dealSchema) {
            throw new HubspotCommerceSchemaMissingException('The DEAL schema is missing from the database.');
        }
        $orderSettings = OrderSettings::fromHubspotObject($dealSchema);
        $orderSettings->validate();

        $properties = [];

        foreach ($orderSettings->getArrayKeys() as $key) {
            if (!$model[$key]) {
                continue;
            }
            $properties[] = [
                'name' => $orderSettings[$key],
                'value' => $model[$key],
            ];
        }

        return $properties;
    }

    /**
     * Creates a deal in Hubspot. If the deal already exists, then updates the existing deal.
     * @param HubspotOrder $model
     *
     * @throws Exception
     * @throws JsonException
     * @throws HubspotCommerceSchemaMissingException
     */
    public function upsertToHubspot($model): int|false
    {
        $properties = $this->mapProperties($model);
        try {
            $res = $this->hubspot->deals()->create($properties);
            return $res->getData()->dealId;
        } catch (BadRequest $e) {
            // Read the exception message into a JSON object
            $res = json_decode($e->getResponse()->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
            $existingObjectId = $res['errorTokens']['existingObjectId'][0] ?? null;
            if ($existingObjectId) {
                $this->hubspot->deals()->update($existingObjectId, $properties);
                return $existingObjectId;
            }
        }

        return false;
    }

    /**
     * Deletes line items from a deal in Hubspot
     *
     * @throws BadRequest
     */
    public function deleteLineItemsFromHubspot(int $dealId): void
    {
        $res = $this->hubspot->crmAssociations()->get($dealId, CrmAssociations::DEAL_TO_LINE_ITEM, ['limit' => self::MAX_LIMIT]);
        $lineItemIds = $res->getData()?->results ?? [];

        $this->hubspot->lineItems()->deleteBatch($lineItemIds);
    }
}
