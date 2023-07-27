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

use batchnz\hubspotecommercebridge\models\HubspotProduct;
use batchnz\hubspotecommercebridge\Plugin;

use Craft;
use craft\queue\BaseJob;

/**
 *
 * @author    Daniel Siemers
 * @package   HubspotEcommerceBridge
 * @since     1.0.0
 */
class DeleteProductJob extends BaseJob
{
    public int $productId;
    public string $sku;

    // Public Methods
    // =========================================================================

    /**
     * When the Queue is ready to run your job, it will call this method.
     * @throws \Exception
     */
    public function execute($queue): void
    {
        $productService = Plugin::getInstance()->getProduct();

        try {
            $productModel = new HubspotProduct();
            $productModel->sku = $this->sku;
            $success = $productService->deleteFromHubspot($productModel);
            if (!$success) {
                throw new \RuntimeException();
            }
        } catch (\Exception $e) {
            Craft::error($e->getMessage(), Plugin::HANDLE);
            throw new \RuntimeException('Failed Delete to Product with ID: ' . $this->productId . " from Hubspot: " . $e->getMessage());
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
        return Craft::t('hub-spot-ecommerce-bridge', "Delete Craft Commerce Product from HubSpot");
    }
}
