<?php

namespace batchnz\hubspotecommercebridge\services;

use batchnz\hubspotecommercebridge\Plugin;
use batchnz\hubspotecommercebridge\records\HubspotCommerceObject;
use craft\base\Component;
use craft\base\Model;

/**
 * Class SettingsService
 * @package batchnz\hubspotecommercebridge\services
 *
 * Handles saving settings for HubSpot objects from the front end
 */
class SettingsService extends Component
{
    /**
     * Handles settings being saved from the front end.
     */
    public function saveDb(Model $settings, string $objectType): bool
    {
        $hubspotObject = HubspotCommerceObject::findOne(['objectType' => $objectType]);

        if (!$hubspotObject) {
            return false;
        }

        $hubspotObject->settings = $settings->attributes;

        if (! $hubspotObject->validate()) {
            return false;
        }

        $hubspotObject->save();
        return true;
    }

    /**
     * Handles settings sent to the HubSpot API.
     * @throws \SevenShores\Hubspot\Exceptions\BadRequest
     */
    public function saveApi(): bool
    {
        $hubspotApi = Plugin::getInstance()->getHubSpot();

        $mappingService = Plugin::getInstance()->getMapping();
        $settingsUpsert = $mappingService->createSettings();

        $apiSettings = $hubspotApi->ecommerceBridge()->upsertSettings($settingsUpsert);

        //TODO more advanced checks to see if the settings went through
        if ($apiSettings->mappings) {
            return true;
        }

        return false;
    }
}
