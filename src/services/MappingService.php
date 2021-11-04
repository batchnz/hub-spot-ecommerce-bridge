<?php

namespace batchnz\hubspotecommercebridge\services;

use batchnz\hubspotecommercebridge\enums\HubSpotObjectTypes;
use batchnz\hubspotecommercebridge\records\HubspotCommerceObject;
use craft\base\Component;

class MappingService extends Component
{
    /**
     * @throws \JsonException
     */
    public function createObjectMapping(string $objectType): array
    {
        $hubspotObject = HubspotCommerceObject::findOne(['objectType' => $objectType]);

        $settings = json_decode($hubspotObject->settings, false, 512, JSON_THROW_ON_ERROR);

        $properties = [];

        foreach ($settings as $externalPropertyName => $hubspotPropertyName) {
            if ($hubspotPropertyName) {
                $properties[] = $this->createPropertyMapping($externalPropertyName, $hubspotPropertyName);
            }
        }

        return (["properties" => $properties]);
    }

    public function createPropertyMapping(string $externalPropertyName, string $hubspotPropertyName, string $dataType = null): array
    {
        return ([
            "externalPropertyName" => $externalPropertyName,
            "hubspotPropertyName" => $hubspotPropertyName,
        ]);
    }

    /**
     * Creates the mappings settings for Craft Commerce objects to HubSpot objects
     * @return array
     * @throws \JsonException
     */
    public function createSettings(): array
    {
        return ([
            "enabled" => true,
            "webhookUri" => null,
            "mappings" => [
                HubSpotObjectTypes::CONTACT =>
                    $this->createObjectMapping(HubSpotObjectTypes::CONTACT),

                HubSpotObjectTypes::DEAL =>
                    $this->createObjectMapping(HubSpotObjectTypes::DEAL),

                HubSpotObjectTypes::PRODUCT =>
                    $this->createObjectMapping(HubSpotObjectTypes::PRODUCT),

                HubSpotObjectTypes::LINE_ITEM =>
                    $this->createObjectMapping(HubSpotObjectTypes::LINE_ITEM),
            ]
        ]);
    }
}
