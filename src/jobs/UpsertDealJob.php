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
use craft\commerce\elements\Order;
use craft\queue\BaseJob;

/**
 *
 * @author    Daniel Siemers
 * @package   HubspotEcommerceBridge
 * @since     1.0.0
 */
class UpsertDealJob extends BaseJob
{
    public int $orderId;

    // Public Methods
    // =========================================================================

    /**
     * When the Queue is ready to run your job, it will call this method.
     * @throws \Exception
     */
    public function execute($queue): void
    {
        $productService = Plugin::getInstance()->getProduct();
        $customerService = Plugin::getInstance()->getCustomer();
        $orderService = Plugin::getInstance()->getOrder();
        $lineItemService = Plugin::getInstance()->getLineItem();

        $craftOrder = Order::findOne(['id' => $this->orderId]);
        $customerId = $craftOrder?->getCustomer()?->getId();
        $lineItems = $craftOrder?->getLineItems();

        $hubspotOrderModel = $orderService->fetch($this->orderId);
        $hubspotDealId = $orderService->upsertToHubspot($hubspotOrderModel);

        if ($customerId) {
            $hubspotCustomerModel = $customerService->fetch($customerId);
            $hubspotContactId = $customerService->upsertToHubspot($hubspotCustomerModel);
            if ($hubspotContactId) {
                $customerService->associateToDeal($hubspotContactId, $hubspotDealId);
            }
        }

        $orderService->deleteLineItemsFromHubspot($hubspotDealId);

        foreach ($lineItems as $lineItem) {
            $craftProductId = $lineItem->getPurchasable()?->getId();
            $hubspotProductModel = $productService->fetch($craftProductId);
            $hubspotProductId = $productService->upsertToHubspot($hubspotProductModel);

            $hubspotLineItemModel = $lineItemService->fetch($lineItem->id);
            $hubspotLineItemModel->productId = (string)$hubspotProductId;
            $hubspotLineItemId = $lineItemService->upsertToHubspot($hubspotLineItemModel);

            try {
                $lineItemService->associateToDeal($hubspotLineItemId, $hubspotDealId);
            } catch (\Exception $e) {
                Craft::error($e->getMessage(), Plugin::HANDLE);
                throw new \RuntimeException('Failed to Associate LineItem with ID: ' . $lineItem->id . " to Order with ID: " . $craftOrder?->getId() . " in Hubspot: " . $e->getMessage());
            }
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
        return Craft::t('hub-spot-ecommerce-bridge', 'Upsert Craft Commerce Deal to HubSpot');
    }
}
