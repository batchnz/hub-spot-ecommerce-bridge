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

use batchnz\hubspotecommercebridge\jobs\DeleteContactJob;
use batchnz\hubspotecommercebridge\jobs\UpsertContactJob;
use Craft;
use craft\elements\User;
use craft\events\ModelEvent;

class CustomerListener
{
    public static function upsert(ModelEvent $event): void
    {
        /** @var User $user */
        $user = $event->sender;
        $queue = Craft::$app->getQueue();

        $queue->push(new UpsertContactJob([
            'customerId' => $user->id,
        ]));
    }

    public static function delete(ModelEvent $event): void
    {
        /** @var User $user */
        $user = $event->sender;
        $queue = Craft::$app->getQueue();

        $queue->push(new DeleteContactJob([
            'customerId' => $user->id,
        ]));
    }
}
