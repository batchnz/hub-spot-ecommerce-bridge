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

class OrderListener
{
    public static function upsert(ModelEvent $event): void
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
        $orderStatus = $order->getOrderStatus();
        //TODO make this dynamic dependant on the settings set by the user
        return ([
            "orderId" => $order->id ?? '',
            "total" => $order->total ?? '',
            "dateCreated" => $order->dateCreated->format('Y-m-d\TH:i:sP') ?? '',
            "orderStatus" => $orderStatus ? $orderStatus->handle : $orderStatus,
            "customerId" => $order->customerId ?? '',
            "orderShortNumber" => $order->reference ?? '',
            "orderNumber" => $order->number ?? '',
            "discountAmount" => $order->totalDiscount ?? '',
            "discountCode" => $order->couponCode ?? '',
        ]);
    }
}
