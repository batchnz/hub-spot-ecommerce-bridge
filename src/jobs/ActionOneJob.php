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
class ActionOneJob extends BaseJob
{
    public string $objectType;

    public array $object;

    public string $action;

    // Public Methods
    // =========================================================================

    /**
     * When the Queue is ready to run your job, it will call this method.
     * @throws \Exception
     */
    public function execute($queue): void
    {
        $importService = Plugin::getInstance()->getImport();

        $messages = $importService->prepareMessages($this->objectType, $this->action, [$this->object]);

        $hubspot = Plugin::getInstance()->getHubSpot();

        $totalMessages = count($messages);
        $completedMessages = 0;

        //Import Object
        foreach ($messages as $message) {
            $completedMessages++;
            $this->setProgress(
                $queue,
                $completedMessages/$totalMessages,
                Craft::t('app', '{step, number} of {total, number}', [
                    'step' => $completedMessages,
                    'total' => $totalMessages,
                ])
            );

//            try {
            $hubspot->ecommerceBridge()->sendSyncMessages(Craft::parseEnv(Plugin::getInstance()->getSettings()->storeId), $this->objectType, $message);
//            } catch (\Throwable $e) {
//                // Don’t let an exception block the queue
//                Craft::warning("Something went wrong: {$e->getMessage()}", __METHOD__);
//            }
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
        return Craft::t('hub-spot-ecommerce-bridge', 'Upsert Craft Commerce Data to HubSpot');
    }
}
