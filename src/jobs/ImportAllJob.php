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

use batchnz\hubspotecommercebridge\enums\HubSpotActionTypes;
use batchnz\hubspotecommercebridge\enums\HubSpotObjectTypes;
use batchnz\hubspotecommercebridge\Plugin;

use Craft;
use craft\queue\BaseJob;
use SevenShores\Hubspot\Factory as HubSpotFactory;

/**
 *
 * $queue = Craft::$app->getQueue();
 * $jobId = $queue->push(new ImportAllJob([
 *     'description' => Craft::t('hub-spot-ecommerce-bridge', 'This overrides the default description'),
 *     'someAttribute' => 'someValue',
 * ]));
 *
 * The key/value pairs that you pass in to the job will set the public properties
 * for that object. Thus whatever you set 'someAttribute' to will cause the
 * public property $someAttribute to be set in the job.
 *
 * Passing in 'description' is optional, and only if you want to override the default
 * description.
 *
 *
 * @author    Daniel Siemers
 * @package   HubspotEcommerceBridge
 * @since     1.0.0
 */
class ImportAllJob extends BaseJob
{
    // Public Methods
    // =========================================================================

    /**
     * When the Queue is ready to run your job, it will call this method.
     */
    public function execute($queue)
    {
        $importService = Plugin::getInstance()->getImport();

        $products = $importService->fetchProducts();
        $productsMessages = $importService->prepareMessages(HubSpotObjectTypes::PRODUCT, HubSpotActionTypes::UPSERT, $products);

        $customers = $importService->fetchCustomers();
        $customersMessages = $importService->prepareMessages(HubSpotObjectTypes::CONTACT, HubSpotActionTypes::UPSERT, $customers);

        $orders = $importService->fetchOrders();
        $orderMessages = $importService->prepareMessages(HubSpotObjectTypes::DEAL, HubSpotActionTypes::UPSERT, $orders);

        $lineItems = $importService->fetchLineItems();
        $lineItemsMessages = $importService->prepareMessages(HubSpotObjectTypes::LINE_ITEM, HubSpotActionTypes::UPSERT, $lineItems);

        $totalMessages = count($productsMessages) + count($customersMessages) + count($orderMessages) + count($lineItemsMessages);
        $completedMessages = 0;

        $hubspot = Plugin::getInstance()->getHubSpot();

        //Import Products
        foreach($productsMessages as $productsMessage) {
            $completedMessages++;
            $this->setProgress(
                $queue,
                $completedMessages/$totalMessages,
                Craft::t('app', '{step, number} of {total, number}', [
                    'step' => $completedMessages,
                    'total' => $totalMessages,
                ])
            );

            try {
                $hubspot->ecommerceBridge()->sendSyncMessages(Plugin::STORE_ID, HubSpotObjectTypes::PRODUCT, $productsMessage);
            } catch (\Throwable $e) {
                // Don’t let an exception block the queue
                Craft::warning("Something went wrong: {$e->getMessage()}", __METHOD__);
            }
        }

        //Import Customers
        foreach($customersMessages as $customersMessage) {
            $completedMessages++;
            $this->setProgress(
                $queue,
                $completedMessages / $totalMessages,
                Craft::t('app', '{step, number} of {total, number}', [
                    'step' => $completedMessages,
                    'total' => $totalMessages,
                ])
            );

            try {
                $hubspot->ecommerceBridge()->sendSyncMessages(Plugin::STORE_ID, HubSpotObjectTypes::CONTACT, $customersMessage);
            } catch (\Throwable $e) {
                // Don’t let an exception block the queue
                Craft::warning("Something went wrong: {$e->getMessage()}", __METHOD__);
            }
        }

        //Import Orders
        foreach($orderMessages as $orderMessage) {
            $completedMessages++;
            $this->setProgress(
                $queue,
                $completedMessages/$totalMessages,
                Craft::t('app', '{step, number} of {total, number}', [
                    'step' => $completedMessages,
                    'total' => $totalMessages,
                ])
            );

            try {
                $hubspot->ecommerceBridge()->sendSyncMessages(Plugin::STORE_ID, HubSpotObjectTypes::DEAL, $orderMessage);
            } catch (\Throwable $e) {
                // Don’t let an exception block the queue
                Craft::warning("Something went wrong: {$e->getMessage()}", __METHOD__);
            }
        }

        //Import LineItems
        foreach($lineItemsMessages as $lineItemsMessage) {
            $completedMessages++;
            $this->setProgress(
                $queue,
                $completedMessages/$totalMessages,
                Craft::t('app', '{step, number} of {total, number}', [
                    'step' => $completedMessages,
                    'total' => $totalMessages,
                ])
            );

            try {
                $hubspot->ecommerceBridge()->sendSyncMessages(Plugin::STORE_ID, HubSpotObjectTypes::LINE_ITEM, $lineItemsMessage);
            } catch (\Throwable $e) {
                // Don’t let an exception block the queue
                Craft::warning("Something went wrong: {$e->getMessage()}", __METHOD__);
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
        return Craft::t('hub-spot-ecommerce-bridge', 'Import All Craft Commerce Data to HubSpot');
    }
}
