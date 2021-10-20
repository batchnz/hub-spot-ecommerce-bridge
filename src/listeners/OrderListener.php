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

use batchnz\hubspotecommercebridge\Plugin;

use Craft;
use craft\queue\BaseJob;
use yii\base\Event;

class OrderListener
{
    public static function upsert(Event $event) {
        return null;
    }
}
