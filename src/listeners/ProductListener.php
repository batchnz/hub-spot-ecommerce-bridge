<?php


/**
 * HubSpot Ecommerce Bridge plugin for Craft CMS 3.x
 *
 * Uses the HubSpot Ecommerce Bridge to sync data from Craft Commerce
 *
 * @link      https://www.batch.nz/
 * @copyright Copyright (c) 2021 Daniel Siemers
 */

namespace batchnz\hubspotecommercebridge\listeners;

use batchnz\hubspotecommercebridge\enums\HubSpotActionTypes;
use batchnz\hubspotecommercebridge\enums\HubSpotObjectTypes;
use batchnz\hubspotecommercebridge\jobs\ActionOneJob;
use Craft;
use craft\events\ModelEvent;
use yii\base\Event;

class ProductListener
{
    public static function upsert(ModelEvent $event): void
    {
        $variant = self::modelProduct($event->sender);
        $queue = Craft::$app->getQueue();

        $queue->push(new ActionOneJob([
            "description" => Craft::t('hub-spot-ecommerce-bridge', 'Upsert Craft Commerce Product Data to HubSpot'),
            "objectType" => HubSpotObjectTypes::PRODUCT,
            "action" => HubSpotActionTypes::UPSERT,
            "object" => $variant,
        ]));
    }

    public static function delete(Event $event): void
    {
        $variant = self::modelProduct($event->sender);
        $queue = Craft::$app->getQueue();

        $queue->push(new ActionOneJob([
            "description" => Craft::t('hub-spot-ecommerce-bridge', 'Delete Craft Commerce Product Data from HubSpot'),
            "objectType" => HubSpotObjectTypes::PRODUCT,
            "action" => HubSpotActionTypes::DELETE,
            "object" => $variant,
        ]));
    }

    protected static function modelProduct($variant): array
    {
        //TODO make this dynamic dependant on the settings set by the user
        return ([
            "price" => $variant->price ?? '',
            "sku" => $variant->sku ?? '',
            "title" => $variant->title ?? '',
        ]);
    }
}
