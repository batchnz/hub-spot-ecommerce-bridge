<?php

namespace batchnz\hubspotecommercebridge\services;

use batchnz\hubspotecommercebridge\enums\HubSpotAssocitations;
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
use SevenShores\Hubspot\Factory;
use yii\base\Exception;

/**
 * Class CustomerService
 * @package batchnz\hubspotecommercebridge\services
 *
 * Handles all of the logic to do with importing Contacts from Craft Commerce to the HubSpot store
 */
class CustomerService extends Component implements HubspotServiceInterface
{
    private Factory $hubspot;

    public function __construct($config = [])
    {
        parent::__construct($config);
        $this->hubspot = Plugin::getInstance()->getHubSpot();
    }

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
    public function upsertToHubspot($model): int|false
    {
        $properties = $this->mapProperties($model);
        try {
            $res = $this->hubspot->contacts()->create($properties);
            return $res->getData()['objectId'];
        } catch (BadRequest $e) {
            // Read the exception message into a JSON object
            $res = json_decode($e->getResponse()->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
            $existingObjectId = $res['errorTokens']['existingObjectId'][0] ?? null;
            if ($existingObjectId) {
                $this->hubspot->contacts()->update($existingObjectId, $properties);
                return $existingObjectId;
            }
        }

        return false;
    }

    /**
     * Associates a Contact with a Deal in Hubspot
     * @throws BadRequest
     */
    public function associateToDeal(int $hubspotContactId, $hubspotDealId): void
    {
        $this->hubspot->crmAssociations()->create([
            "fromObjectId" => $hubspotContactId,
            "toObjectId" => $hubspotDealId,
            "category" => "HUBSPOT_DEFINED",
            "definitionId" => HubSpotAssocitations::CONTACT_TO_DEAL,
        ]);
    }
}
