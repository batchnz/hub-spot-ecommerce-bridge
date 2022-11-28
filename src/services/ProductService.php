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
class ProductService extends Component
{
    /**
     * Fetches a product with it's associated product ID and returns it in
     * an object with only the attributes required by Hubspot
     * @param int $productId
     * @return HubspotProduct
     * @throws CraftCommerceObjectMissing
     */
    public function fetchProduct(int $productId): HubspotProduct
    {
        $variant = Variant::findOne(['id' => $productId]);
        if (!$variant) {
            throw new CraftCommerceObjectMissing('Could not fetch Variant with ID: ' . $productId);
        }
        return HubspotProduct::fromCraftProduct($variant);
    }

    /**
     * Maps properties to a format suitable to be included in the request to Hubspot
     * @throws Exception
     * @throws JsonException
     * @throws HubspotCommerceSchemaMissingException
     */
    public function mapProductProperties(HubspotProduct $product): array
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
                'value' => $product[$key],
            ];
        }

        return $properties;
    }

    /**
     * Creates a product in Hubspot. If the product already exists, then updates the existing product.
     *
     * @throws Exception
     * @throws JsonException
     * @throws HubspotCommerceSchemaMissingException
     */
    public function upsertToHubspot(HubspotProduct $product): bool
    {
        $properties = $this->mapProductProperties($product);
        $hubspot = Plugin::getInstance()->getHubSpot();

        try {
            $hubspot->products()->create($properties);
            return true;
        } catch (BadRequest $e) {
            // Read the exception message into a JSON object
            $res = json_decode($e->getResponse()->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
            $existingObjectId = $res['errorTokens']['existingObjectId'][0] ?? null;
            if ($existingObjectId) {
                $hubspot->products()->update($existingObjectId, $properties);
                return true;
            }
        }

        return false;
    }
}
