<?php

namespace batchnz\hubspotecommercebridge\services;

use batchnz\hubspotecommercebridge\enums\HubSpotDataTypes;
use batchnz\hubspotecommercebridge\enums\HubSpotObjectTypes;
use batchnz\hubspotecommercebridge\Plugin;
use craft\base\Component;

class MappingService extends Component
{

    public function createObjectMapping(array $properties): array
    {
        return (["properties" => $properties]);
    }

    public function createPropertyMapping(string $externalPropertyName, string $hubspotPropertyName, string $dataType): array
    {
        return ([
            "externalPropertyName" => $externalPropertyName,
            "hubspotPropertyName" => $hubspotPropertyName,
            "dataType" => $dataType,
        ]);
    }

    /**
     * Creates the mappings settings for Craft Commerce objects to HubSpot objects
     * @return array
     */
    public function createSettings() : array
    {
        return ([
            "enabled" => true,
            "webhookUri" => Plugin::WEBHOOK_URI,
            "mappings" => [
                HubSpotObjectTypes::CONTACT =>
                    $this->createObjectMapping([
                        $this->createPropertyMapping("email", "email", HubSpotDataTypes::STRING),
                        $this->createPropertyMapping("firstName", "firstname", HubSpotDataTypes::STRING),
                        $this->createPropertyMapping("lastName", "lastname", HubSpotDataTypes::STRING),
                    ]),


                HubSpotObjectTypes::DEAL =>
                    $this->createObjectMapping([
                        $this->createPropertyMapping("totalPrice", "amount", HubSpotDataTypes::STRING),
                        $this->createPropertyMapping("dateOrdered", "createdate", HubSpotDataTypes::DATETIME),
                        $this->createPropertyMapping("orderStage", "dealstage", HubSpotDataTypes::STRING),
                    ]),


                HubSpotObjectTypes::PRODUCT =>
                    $this->createObjectMapping([
                        $this->createPropertyMapping("price", "price", HubSpotDataTypes::NUMBER),
                        $this->createPropertyMapping("sku", "hs_sku", HubSpotDataTypes::STRING),
                        $this->createPropertyMapping("title", "name", HubSpotDataTypes::STRING),
                    ]),


                HubSpotObjectTypes::LINE_ITEM =>
                    $this->createObjectMapping([
                        $this->createPropertyMapping("description", "description", HubSpotDataTypes::STRING),
                        $this->createPropertyMapping("sku", "hs_sku", HubSpotDataTypes::STRING),
                    ]),
            ]
        ]);
    }
}
