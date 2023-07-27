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

use batchnz\hubspotecommercebridge\Plugin;

use Craft;
use craft\queue\BaseJob;

/**
 *
 * @author    Daniel Siemers
 * @package   HubspotEcommerceBridge
 * @since     1.0.0
 */
class DeleteContactJob extends BaseJob
{
    public int $customerId;

    // Public Methods
    // =========================================================================

    /**
     * When the Queue is ready to run your job, it will call this method.
     * @throws \Exception
     */
    public function execute($queue): void
    {
        $customerService = Plugin::getInstance()->getCustomer();

        try {
            $hubspotContact = $customerService->fetch($this->customerId);
            $success = $customerService->deleteFromHubspot($hubspotContact);
            if (!$success) {
                throw new \RuntimeException();
            }
        } catch (\Exception $e) {
            Craft::error($e->getMessage(), Plugin::HANDLE);
            throw new \RuntimeException('Failed Delete to Customer with ID: ' . $this->customerId . " from Hubspot: " . $e->getMessage());
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
        return Craft::t('hub-spot-ecommerce-bridge', "Delete Craft Commerce Customer from HubSpot");
    }
}
