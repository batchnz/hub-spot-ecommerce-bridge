<?php

namespace batchnz\hubspotecommercebridge\services;

use batchnz\hubspotecommercebridge\enums\HubSpotObjectTypes;
use batchnz\hubspotecommercebridge\exceptions\CraftCommerceObjectMissing;
use batchnz\hubspotecommercebridge\exceptions\HubspotCommerceSchemaMissingException;
use batchnz\hubspotecommercebridge\exceptions\ProcessingSettingsException;
use batchnz\hubspotecommercebridge\models\HubspotProduct;
use batchnz\hubspotecommercebridge\models\ProductSettings;
use batchnz\hubspotecommercebridge\Plugin;
use batchnz\hubspotecommercebridge\records\HubspotCommerceObject;
use craft\base\Component;
use craft\commerce\elements\Variant;
use Exception;
use HubSpot\Client\Crm\Products\ApiException;
use HubSpot\Client\Crm\Products\Model\Filter;
use HubSpot\Client\Crm\Products\Model\FilterGroup;
use HubSpot\Client\Crm\Products\Model\PublicObjectSearchRequest;
use HubSpot\Client\Crm\Products\Model\SimplePublicObjectInput;
use Hubspot\Discovery\Discovery as HubSpotApi;

/**
 * Class ProductService
 * @package batchnz\hubspotecommercebridge\services
 *
 * Handles all the logic to do with importing Products from Craft Commerce to the HubSpot store
 */
class ProductService extends Component implements HubspotServiceInterface
{
    private HubSpotApi $hubspot;
    private ProductSettings $settings;

    /**
     * @throws HubspotCommerceSchemaMissingException
     * @throws ProcessingSettingsException
     */
    public function __construct($config = [])
    {
        parent::__construct($config);
        $this->hubspot = Plugin::getInstance()->getHubSpot();
        $productSchema = HubspotCommerceObject::findOne(['objectType' => HubSpotObjectTypes::PRODUCT]);
        if (!$productSchema) {
            throw new HubspotCommerceSchemaMissingException('The PRODUCT schema is missing from the database.');
        }
        try {
            $productSettings = ProductSettings::fromHubspotObject($productSchema);
        } catch (Exception $e) {
            throw new ProcessingSettingsException('Failed to process the settings for PRODUCT.' . $e->getMessage());
        }
        $productSettings->validate();
        $this->settings = $productSettings;
    }

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
     * Finds a product in hubspot using its unique key
     *
     * @param HubspotProduct $model
     * @return int|false
     * @throws ApiException
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

        $res = $this->hubspot->crm()->products()->searchApi()->doSearch($searchReq);
        return count($res->getResults()) ? $res->getResults()[0]->getId() : false;
    }

    /**
     * Creates a product in Hubspot. If the product already exists, then updates the existing product.
     * @param HubspotProduct $model
     * @throws ApiException
     */
    public function upsertToHubspot($model): string|false
    {
        $properties = $this->mapProperties($model);
        $existingObjectId = $this->findInHubspot($model);
        $productInput = new SimplePublicObjectInput();
        if ($existingObjectId) {
            // Don't upsert the unique key
            unset($properties[$this->settings[$this->settings->uniqueKey()]]);
            $productInput->setProperties($properties);
            $res = $this->hubspot->crm()->products()->basicApi()->update($existingObjectId, $productInput);
        } else {
            $productInput->setProperties($properties);
            $res = $this->hubspot->crm()->products()->basicApi()->create($productInput);
        }
        return $res->getId();
    }

    /**
     * Deletes a product from Hubspot.
     *
     * @param HubspotProduct $model
     * @return int|false
     * @throws ApiException
     */
    public function deleteFromHubspot($model): int|false
    {
        $existingObjectId = $this->findInHubspot($model);
        if (!$existingObjectId) {
            return false;
        }
        $this->hubspot->crm()->products()->basicApi()->archive($existingObjectId);
        return $existingObjectId;
    }
}
