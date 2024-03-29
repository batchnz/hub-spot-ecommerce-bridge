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

use batchnz\hubspotecommercebridge\jobs\DeleteDealJob;
use batchnz\hubspotecommercebridge\jobs\UpsertDealJob;
use Craft;
use craft\commerce\elements\Order;
use yii\base\Event;

class OrderListener
{
    public static function upsert(Event $event): void
    {
        /** @var Order $order */
        $order = $event->sender;
        $queue = Craft::$app->getQueue();

        $queue->push(new UpsertDealJob([
            'orderId' => $order->id,
        ]));
    }

    public static function delete(Event $event): void
    {
        /** @var Order $order */
        $order = $event->sender;
        $queue = Craft::$app->getQueue();

        $queue->push(new DeleteDealJob([
            'orderId' => $order->id,
            'orderNumber' => $order->number,
        ]));
    }
}
