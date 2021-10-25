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
use craft\events\ModelEvent;
use SevenShores\Hubspot\Factory as HubSpotFactory;
use yii\base\Event;

class OrderListener
{
    public static function upsert(ModelEvent $event)
    {
        $order = self::modelOrder($event->sender);
        $queue = Craft::$app->getQueue();



        $queue->push(new ActionOneJob([
            "description" => Craft::t('hub-spot-ecommerce-bridge', 'Upsert Craft Commerce Order Data to HubSpot'),
            "objectType" => HubSpotObjectTypes::DEAL,
            "action" => HubSpotActionTypes::UPSERT,
            "object" => $order,
        ]));
    }

    public static function delete(Event $event): void
    {
        $order = self::modelOrder($event->sender);
        $queue = Craft::$app->getQueue();

        $queue->push(new ActionOneJob([
            "description" => Craft::t('hub-spot-ecommerce-bridge', 'Delete Craft Commerce Order Data from HubSpot'),
            "objectType" => HubSpotObjectTypes::DEAL,
            "action" => HubSpotActionTypes::DELETE,
            "object" => $order,
        ]));
    }

    protected static function modelOrder($order): array
    {
        //TODO make this dynamic dependant on the settings set by the user
        return ([
            "orderId" => $order->id,
            "total" => $order->total,
            "dateOrdered" => "",
            "orderStatus" => $order->getOrderStatus() ? $order->getOrderStatus()->handle : $order->getOrderStatus(),
            "customerId" => $order->customerId,
        ]);
    }
}
