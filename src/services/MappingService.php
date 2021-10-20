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
}
