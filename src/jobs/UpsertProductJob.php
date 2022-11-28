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
class UpsertProductJob extends BaseJob
{
    public int $productId;

    // Public Methods
    // =========================================================================

    /**
     * When the Queue is ready to run your job, it will call this method.
     */
    public function execute($queue): void
    {
        $productService = Plugin::getInstance()->getProduct();

        try {
            $hubspotProduct = $productService->fetchProduct($this->productId);
            $success = $productService->upsertToHubspot($hubspotProduct);
            if (!$success) {
                throw new \RuntimeException();
            }
        } catch (\Exception $e) {
            Craft::error($e->getMessage(), Plugin::HANDLE);
            throw new \RuntimeException('Failed Upsert Product with ID: ' . $this->productId . " to Hubspot");
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
        return Craft::t('hub-spot-ecommerce-bridge', 'Upsert Craft Commerce Product to HubSpot');
    }
}
