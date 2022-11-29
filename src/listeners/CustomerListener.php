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
use batchnz\hubspotecommercebridge\jobs\UpsertContactJob;
use Craft;
use craft\commerce\events\LineItemEvent;
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

//    public static function delete(LineItemEvent $event): void
//    {
//        $lineItem = self::modelCustomer($event->lineItem);
//        $queue = Craft::$app->getQueue();
//
//        $queue->push(new ActionOneJob([
//            "description" => Craft::t('hub-spot-ecommerce-bridge', 'Delete Craft Commerce Line Item Data from HubSpot'),
//            "objectType" => HubSpotObjectTypes::LINE_ITEM,
//            "action" => HubSpotActionTypes::DELETE,
//            "object" => $lineItem,
//        ]));
//    }
}
