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

use batchnz\hubspotecommercebridge\models\HubspotOrder;
use batchnz\hubspotecommercebridge\Plugin;

use Craft;
use craft\queue\BaseJob;

/**
 *
 * @author    Daniel Siemers
 * @package   HubspotEcommerceBridge
 * @since     1.0.0
 */
class DeleteDealJob extends BaseJob
{
    public int $orderId;
    public string $orderNumber;

    // Public Methods
    // =========================================================================

    /**
     * When the Queue is ready to run your job, it will call this method.
     * @throws \Exception
     */
    public function execute($queue): void
    {
        $orderService = Plugin::getInstance()->getOrder();

        try {
            $orderModel = new HubspotOrder();
            $orderModel->orderNumber = $this->orderNumber;
            $hubspotDealId = $orderService->findInHubspot($orderModel);
            $orderService->deleteLineItemsFromHubspot($hubspotDealId);
            $success = $orderService->deleteFromHubspot($orderModel);
            if (!$success) {
                throw new \RuntimeException();
            }
        } catch (\Exception $e) {
            Craft::error($e->getMessage(), Plugin::HANDLE);
            throw new \RuntimeException('Failed Delete to Order with ID: ' . $this->orderId . " from Hubspot: " . $e->getMessage());
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
        return Craft::t('hub-spot-ecommerce-bridge', "Delete Craft Commerce Order from HubSpot");
    }
}
