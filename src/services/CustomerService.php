<?php

namespace batchnz\hubspotecommercebridge\services;

use batchnz\hubspotecommercebridge\enums\HubSpotAssociations;
use batchnz\hubspotecommercebridge\enums\HubSpotObjectTypes;
use batchnz\hubspotecommercebridge\exceptions\CraftCommerceObjectMissing;
use batchnz\hubspotecommercebridge\exceptions\HubspotCommerceSchemaMissingException;
use batchnz\hubspotecommercebridge\exceptions\ProcessingSettingsException;
use batchnz\hubspotecommercebridge\models\CustomerSettings;
use batchnz\hubspotecommercebridge\models\HubspotCustomer;
use batchnz\hubspotecommercebridge\Plugin;
use batchnz\hubspotecommercebridge\records\HubspotCommerceObject;
use craft\base\Component;
use craft\elements\User;
use HubSpot\Client\Crm\Contacts\ApiException;
use HubSpot\Client\Crm\Contacts\Model\Filter;
use HubSpot\Client\Crm\Contacts\Model\FilterGroup;
use HubSpot\Client\Crm\Contacts\Model\PublicObjectSearchRequest;
use HubSpot\Client\Crm\Contacts\Model\SimplePublicObjectInput;
use Hubspot\Discovery\Discovery as HubSpotApi;

/**
 * Class CustomerService
 * @package batchnz\hubspotecommercebridge\services
 *
 * Handles all of the logic to do with importing Contacts from Craft Commerce to the HubSpot store
 */
class CustomerService extends Component implements HubspotServiceInterface
{
    private HubspotApi $hubspot;
    private CustomerSettings $settings;

    /**
     * @throws ProcessingSettingsException
     * @throws HubspotCommerceSchemaMissingException
     */
    public function __construct($config = [])
    {
        parent::__construct($config);
        $this->hubspot = Plugin::getInstance()->getHubSpot();
        $contactSchema = HubspotCommerceObject::findOne(['objectType' => HubSpotObjectTypes::CONTACT]);
        if (!$contactSchema) {
            throw new HubspotCommerceSchemaMissingException('The CONTACT schema is missing from the database.');
        }
        try {
            $customerSettings = CustomerSettings::fromHubspotObject($contactSchema);
        } catch (\Exception $e) {
            throw new ProcessingSettingsException('Failed to process the settings for PRODUCT.');
        }
        $customerSettings->validate();
        $this->settings = $customerSettings;
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
     * Finds a customer in hubspot using its unique key
     *
     * @param HubspotCustomer $model
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
            $res = $this->hubspot->crm()->contacts()->searchApi()->doSearch($searchReq);
            return count($res->getResults()) ? $res->getResults()[0]->getId() : false;
        } catch (ApiException $e) {
            return false;
        }
    }

    /**
     * Creates a contact in Hubspot. If the contact already exists, then updates the existing contact.
     * @param HubspotCustomer $model
     */
    public function upsertToHubspot($model): string|false
    {
        $properties = $this->mapProperties($model);
        $existingObjectId = $this->findInHubspot($model);
        $contactInput = new SimplePublicObjectInput();
        try {
            if ($existingObjectId) {
                // Don't upsert the unique key
                unset($properties[$this->settings[$this->settings->uniqueKey()]]);
                $contactInput->setProperties($properties);
                $res = $this->hubspot->crm()->contacts()->basicApi()->update($existingObjectId, $contactInput);
            } else {
                $contactInput->setProperties($properties);
                $res = $this->hubspot->crm()->contacts()->basicApi()->create($contactInput);
            }
            return $res->getId();
        } catch (ApiException $e) {
            return false;
        }
    }

    /**
     * Deletes a contact from Hubspot.
     *
     * @param HubspotCustomer $model
     * @return int|false
     */
    public function deleteFromHubspot($model): int|false
    {
        $existingObjectId = $this->findInHubspot($model);
        if (!$existingObjectId) {
            return false;
        }
        try {
            $this->hubspot->crm()->contacts()->basicApi()->archive($existingObjectId);
            return $existingObjectId;
        } catch (ApiException $e) {
            return false;
        }
    }

    /**
     * Associates a Contact with a Deal in Hubspot
     */
    public function associateToDeal(int $hubspotContactId, $hubspotDealId): void
    {
        $this->hubspot->apiRequest([
            'method' => 'PUT',
            'path' => "/crm-associations/v1/associations",
            'body' => [
                "fromObjectId" => $hubspotContactId,
                "toObjectId" => $hubspotDealId,
                "category" => "HUBSPOT_DEFINED",
                "definitionId" => HubSpotAssociations::CONTACT_TO_DEAL
            ]
        ]);
    }
}
