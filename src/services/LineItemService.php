<?php

namespace batchnz\hubspotecommercebridge\services;

use batchnz\hubspotecommercebridge\enums\HubSpotAssocitations;
use batchnz\hubspotecommercebridge\enums\HubSpotObjectTypes;
use batchnz\hubspotecommercebridge\models\HubspotLineItem;
use batchnz\hubspotecommercebridge\models\LineItemSettings;
use batchnz\hubspotecommercebridge\Plugin;
use batchnz\hubspotecommercebridge\records\HubspotCommerceObject;
use craft\base\Component;
use craft\commerce\records\LineItem;
use CraftCommerceObjectMissing;
use HubspotCommerceSchemaMissingException;
use JsonException;
use SevenShores\Hubspot\Exceptions\BadRequest;
use SevenShores\Hubspot\Factory;
use yii\base\Exception;

/**
 * Class LineItemService
 * @package batchnz\hubspotecommercebridge\services
 *
 * Handles all of the logic to do with importing LineItems from Craft Commerce to the HubSpot store
 */
class LineItemService extends Component implements HubspotServiceInterface
{
    private Factory $hubspot;

    public function __construct($config = [])
    {
        parent::__construct($config);
        $this->hubspot = Plugin::getInstance()->getHubSpot();
    }

    /**
     * Fetches a lineitem with it's associated lineitem ID and returns it in
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
     *
     * @throws Exception
     * @throws JsonException
     * @throws HubspotCommerceSchemaMissingException
     */
    public function mapProperties($model): array
    {
        $lineItemSchema = HubspotCommerceObject::findOne(['objectType' => HubSpotObjectTypes::LINE_ITEM]);
        if (!$lineItemSchema) {
            throw new HubspotCommerceSchemaMissingException('The LINE_ITEM schema is missing from the database.');
        }
        $lineItemSettings = LineItemSettings::fromHubspotObject($lineItemSchema);
        $lineItemSettings->validate();

        $properties = [];

        foreach (array_keys($lineItemSettings->attributes) as $key) {
            $properties[] = [
                'name' => $lineItemSettings[$key],
                'value' => $model[$key],
            ];
        }

        return $properties;
    }

    /**
     * Creates a lineitem in Hubspot. If the lineitem already exists, then lineitem the existing deal.
     * @param HubspotLineItem $model
     *
     * @throws Exception
     * @throws JsonException
     * @throws HubspotCommerceSchemaMissingException
     */
    public function upsertToHubspot($model): int|false
    {
        $properties = $this->mapProperties($model);
        try {
            $res = $this->hubspot->lineItems()->create($properties);
            return $res->getData()['objectId'];
        } catch (BadRequest $e) {
            // Read the exception message into a JSON object
            $res = json_decode($e->getResponse()->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
            $existingObjectId = $res['errorTokens']['existingObjectId'][0] ?? null;
            if ($existingObjectId) {
                $this->hubspot->lineItems()->update($existingObjectId, $properties);
                return $existingObjectId;
            }
        }

        return false;
    }

    /**
     * Associates a LineItem with a Deal in Hubspot
     * @throws BadRequest
     */
    public function associateToDeal(int $hubspotLineItemId, $hubspotDealId): void
    {
        $this->hubspot->crmAssociations()->create([
            "fromObjectId" => $hubspotLineItemId,
            "toObjectId" => $hubspotDealId,
            "category" => "HUBSPOT_DEFINED",
            "definitionId" => HubSpotAssocitations::LINE_ITEM_TO_DEAL,
        ]);
    }
}
