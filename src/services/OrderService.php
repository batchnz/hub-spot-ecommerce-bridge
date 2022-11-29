<?php

namespace batchnz\hubspotecommercebridge\services;

use batchnz\hubspotecommercebridge\enums\HubSpotObjectTypes;
use batchnz\hubspotecommercebridge\models\HubspotOrder;
use batchnz\hubspotecommercebridge\models\OrderSettings;
use batchnz\hubspotecommercebridge\Plugin;
use batchnz\hubspotecommercebridge\records\HubspotCommerceObject;
use craft\base\Component;
use craft\commerce\elements\Order;
use CraftCommerceObjectMissing;
use HubspotCommerceSchemaMissingException;
use JsonException;
use SevenShores\Hubspot\Exceptions\BadRequest;
use yii\base\Exception;

/**
 * Class OrderService
 * @package batchnz\hubspotecommercebridge\services
 *
 * Handles all of the logic to do with importing Orders from Craft Commerce to the HubSpot store
 */
class OrderService extends Component implements HubspotServiceInterface
{
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

        foreach (array_keys($orderSettings->attributes) as $key) {
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
        $hubspot = Plugin::getInstance()->getHubSpot();

        try {
            //TODO: Add deal to pipeline
            $res = $hubspot->deals()->create($properties);
            return $res->getData()['objectId'];
        } catch (BadRequest $e) {
            // Read the exception message into a JSON object
            $res = json_decode($e->getResponse()->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
            $existingObjectId = $res['errorTokens']['existingObjectId'][0] ?? null;
            if ($existingObjectId) {
                $hubspot->deals()->update($existingObjectId, $properties);
                return $existingObjectId;
            }
        }

        return false;
    }
}
