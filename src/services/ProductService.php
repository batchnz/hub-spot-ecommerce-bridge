<?php

namespace batchnz\hubspotecommercebridge\services;

use batchnz\hubspotecommercebridge\enums\HubSpotObjectTypes;
use batchnz\hubspotecommercebridge\models\HubspotProduct;
use batchnz\hubspotecommercebridge\models\ProductSettings;
use batchnz\hubspotecommercebridge\Plugin;
use batchnz\hubspotecommercebridge\records\HubspotCommerceObject;
use craft\base\Component;
use craft\commerce\elements\Variant;
use CraftCommerceObjectMissing;
use HubspotCommerceSchemaMissingException;
use JsonException;
use SevenShores\Hubspot\Exceptions\BadRequest;
use yii\base\Exception;

/**
 * Class ProductService
 * @package batchnz\hubspotecommercebridge\services
 *
 * Handles all of the logic to do with importing Products from Craft Commerce to the HubSpot store
 */
class ProductService extends Component implements HubspotServiceInterface
{
    /**
     * Fetches a product with it's associated product ID and returns it in
     * an object with only the attributes required by Hubspot
     * @param int $id Product ID
     * @return HubspotProduct
     * @throws CraftCommerceObjectMissing
     */
    public function fetch(int $id): HubspotProduct
    {
        $variant = Variant::findOne(['id' => $id]);
        if (!$variant) {
            throw new CraftCommerceObjectMissing('Could not fetch Variant with ID: ' . $id);
        }
        return HubspotProduct::fromCraftModel($variant);
    }

    /**
     * Maps properties to a format suitable to be included in the request to Hubspot
     * @param HubspotProduct $model
     *
     * @throws Exception
     * @throws JsonException
     * @throws HubspotCommerceSchemaMissingException
     */
    public function mapProperties($model): array
    {
        $productSchema = HubspotCommerceObject::findOne(['objectType' => HubSpotObjectTypes::PRODUCT]);
        if (!$productSchema) {
            throw new HubspotCommerceSchemaMissingException('The PRODUCT schema is missing from the database.');
        }
        $productSettings = ProductSettings::fromHubspotObject($productSchema);
        $productSettings->validate();

        $properties = [];

        foreach (array_keys($productSettings->attributes) as $key) {
            $properties[] = [
                'name' => $productSettings[$key],
                'value' => $model[$key],
            ];
        }

        return $properties;
    }

    /**
     * Creates a product in Hubspot. If the product already exists, then updates the existing product.
     * @param HubspotProduct $model
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
            $res = $hubspot->products()->create($properties);
            return $res->getData()['objectId'];
        } catch (BadRequest $e) {
            // Read the exception message into a JSON object
            $res = json_decode($e->getResponse()->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
            $existingObjectId = $res['errorTokens']['existingObjectId'][0] ?? null;
            if ($existingObjectId) {
                $hubspot->products()->update($existingObjectId, $properties);
                return $existingObjectId;
            }
        }

        return false;
    }
}
