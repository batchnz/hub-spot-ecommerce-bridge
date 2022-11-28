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

use batchnz\hubspotecommercebridge\jobs\UpsertProductJob;
use Craft;
use craft\commerce\elements\Variant;
use craft\events\ModelEvent;
use yii\base\Event;

class ProductListener
{
    public static function upsert(ModelEvent $event): void
    {
        /** @var Variant $variant */
        $variant = $event->sender;
        $queue = Craft::$app->getQueue();

        $queue->push(new UpsertProductJob([
            'productId' => $variant->id,
        ]));
    }

    public static function delete(Event $event): void
    {
        $variant = self::modelProduct($event->sender);
        $queue = Craft::$app->getQueue();

//        $queue->push();
    }
}
