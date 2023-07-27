<?php

/**
 * HubSpot Ecommerce Bridge plugin for Craft CMS 3.x
 *
 * Uses the HubSpot Ecommerce Bridge to sync data from Craft Commerce
 *
 * @link      https://www.batch.nz/
 * @copyright Copyright (c) 2021 Daniel Siemers
 */

namespace batchnz\hubspotecommercebridge\jobs;

use Craft;
use craft\queue\BaseJob;

/**
 *
 * @author    Daniel Siemers
 * @package   HubspotEcommerceBridge
 * @since     1.0.0
 */
class SyncDealsJob extends BaseJob
{
    public array $orderIds;

    // Public Methods
    // =========================================================================

    /**
     * When the Queue is ready to run your job, it will call this method.
     */
    public function execute($queue): void
    {
        foreach ($this->orderIds as $orderId) {
            $queue->push(new UpsertDealJob([
                'orderId' => $orderId,
            ]));
        }
    }

    // Protected Methods
    // =========================================================================
    /**
     * Returns a default description for [[getDescription()]], if [[description]] isn’t set.
     *
     * @return string The default task description
     */
    protected function defaultDescription(): string
    {
        return Craft::t('hub-spot-ecommerce-bridge', 'Sync Deals to Hubspot');
    }
}
