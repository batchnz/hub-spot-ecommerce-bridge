<?php

namespace batchnz\hubspotecommercebridge\services;

use batchnz\hubspotecommercebridge\enums\HubSpotObjectTypes;
use batchnz\hubspotecommercebridge\models\CustomerSettings;
use batchnz\hubspotecommercebridge\models\HubspotCustomer;
use batchnz\hubspotecommercebridge\Plugin;
use batchnz\hubspotecommercebridge\records\HubspotCommerceObject;
use craft\base\Component;
use craft\elements\User;
use CraftCommerceObjectMissing;
use HubspotCommerceSchemaMissingException;
use JsonException;
use SevenShores\Hubspot\Exceptions\BadRequest;
use yii\base\Exception;

/**
 * Class CustomerService
 * @package batchnz\hubspotecommercebridge\services
 *
 * Handles all of the logic to do with importing Contacts from Craft Commerce to the HubSpot store
 */
class CustomerService extends Component implements HubspotServiceInterface
{
    /**
     * Fetches a contact with it's associated contact ID and returns it in
     * an object with only the attributes required by Hubspot
     * @param int $id Contact ID
     * @return HubspotCustomer
     * @throws CraftCommerceObjectMissing
     */
    public function fetch(int $id): HubspotCustomer
    {
        $user = User::findOne(['id' => $id]);
        if (!$user) {
            throw new CraftCommerceObjectMissing('Could not fetch Variant with ID: ' . $id);
        }
        return HubspotCustomer::fromCraftModel($user);
    }

    /**
     * Maps properties to a format suitable to be included in the request to Hubspot
     * @param HubspotCustomer $model
     *
     * @throws Exception
     * @throws JsonException
     * @throws HubspotCommerceSchemaMissingException
     */
    public function mapProperties($model): array
    {
        $contactSchema = HubspotCommerceObject::findOne(['objectType' => HubSpotObjectTypes::CONTACT]);
        if (!$contactSchema) {
            throw new HubspotCommerceSchemaMissingException('The CONTACT schema is missing from the database.');
        }
        $customerSettings = CustomerSettings::fromHubspotObject($contactSchema);
        $customerSettings->validate();

        $properties = [];

        foreach (array_keys($customerSettings->attributes) as $key) {
            $properties[] = [
                'name' => $customerSettings[$key],
                'value' => $model[$key],
            ];
        }

        return $properties;
    }

    /**
     * Creates a contact in Hubspot. If the contact already exists, then updates the existing contact.
     * @param HubspotCustomer $model
     *
     * @throws Exception
     * @throws JsonException
     * @throws HubspotCommerceSchemaMissingException
     */
    public function upsertToHubspot($model): bool
    {
        $properties = $this->mapProperties($model);
        $hubspot = Plugin::getInstance()->getHubSpot();

        try {
            $hubspot->contacts()->create($properties);
            return true;
        } catch (BadRequest $e) {
            // Read the exception message into a JSON object
            $res = json_decode($e->getResponse()->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
            $existingObjectId = $res['errorTokens']['existingObjectId'][0] ?? null;
            if ($existingObjectId) {
                $hubspot->contacts()->update($existingObjectId, $properties);
                return true;
            }
        }

        return false;
    }
}
