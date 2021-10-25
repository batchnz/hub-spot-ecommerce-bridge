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
use batchnz\hubspotecommercebridge\Plugin;
use Craft;
use craft\commerce\events\LineItemEvent;
use craft\events\ModelEvent;
use SevenShores\Hubspot\Factory as HubSpotFactory;
use yii\base\Event;

class LineItemListener
{
    public static function upsert(LineItemEvent $event)
    {
        $lineItem = self::modelLineItem($event->lineItem);

        $queue = Craft::$app->getQueue();

        $queue->push(new ActionOneJob([
            "description" => Craft::t('hub-spot-ecommerce-bridge', 'Upsert Craft Commerce Line Item Data to HubSpot'),
            "objectType" => HubSpotObjectTypes::LINE_ITEM,
            "action" => HubSpotActionTypes::UPSERT,
            "object" => $lineItem,
        ]));
    }

    public static function delete(LineItemEvent $event): void
    {
        $lineItem = self::modelLineItem($event->lineItem);
        $queue = Craft::$app->getQueue();

        $queue->push(new ActionOneJob([
            "description" => Craft::t('hub-spot-ecommerce-bridge', 'Delete Craft Commerce Line Item Data from HubSpot'),
            "objectType" => HubSpotObjectTypes::LINE_ITEM,
            "action" => HubSpotActionTypes::DELETE,
            "object" => $lineItem,
        ]));
    }

    protected static function modelLineItem($lineItem): array
    {
        //TODO make this dynamic dependant on the settings set by the user
        return ([
            "lineItemId" => $lineItem->id,
            "description" => $lineItem->getDescription(),
            "qty" => $lineItem->qty,
            "price" => $lineItem->getPrice(),
            "orderId" => $lineItem->getOrder()->id, // Association with related order
            "sku" => $lineItem->getPurchasable()->sku, // Association with related product
        ]);
    }
}
