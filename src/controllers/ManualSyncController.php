<?php

/**
 * HubSpot Ecommerce Bridge plugin for Craft CMS 3.x
 *
 * Uses the HubSpot Ecommerce Bridge to sync data from Craft Commerce
 *
 * @link      https://www.batch.nz/
 * @copyright Copyright (c) 2021 Daniel Siemers
 */

namespace batchnz\hubspotecommercebridge\controllers;

use batchnz\hubspotecommercebridge\jobs\SyncContactsJob;
use batchnz\hubspotecommercebridge\jobs\SyncDealsJob;
use batchnz\hubspotecommercebridge\jobs\SyncProductsJob;
use batchnz\hubspotecommercebridge\Plugin;
use batchnz\hubspotecommercebridge\services\ManualSyncService;
use Craft;
use craft\errors\MissingComponentException;
use craft\helpers\DateTimeHelper;
use craft\web\Controller;
use yii\base\InvalidConfigException;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

/**
 * Manual Sync Controller
 *
 * @author    Daniel Siemers
 * @package   HubspotEcommerceBridge
 * @since     1.0.0
 */
class ManualSyncController extends Controller
{
    private ManualSyncService $manualSyncService;
    // Protected Properties
    // =========================================================================

    /**
    * @var    bool|array Allows anonymous access to this controller's actions.
    *         The actions must be in 'kebab-case'
    * @access protected
    */
    protected array|bool|int $allowAnonymous = true;

    // Public Methods
    // =========================================================================

    /**
     * @throws InvalidConfigException
     * @throws ForbiddenHttpException
     */
    public function init(): void
    {
        $this->requirePermission('accessCp');
        parent::init();
        $this->manualSyncService = Plugin::getInstance()->getManualSync();
    }

    /**
     * Creates the store in HubSpot that will sync with the craft commerce store
     * @return Response
     */
    public function actionEdit(): Response
    {
        return $this->renderTemplate(Plugin::HANDLE . '/manual-sync/_index');
    }

    /**
     * @throws MissingComponentException
     * @throws BadRequestHttpException|InvalidConfigException
     * @throws \Exception
     */
    public function actionSyncProducts(): ?Response
    {
        $this->requirePostRequest();
        $queue = Craft::$app->getQueue();

        $params = Craft::$app->getRequest()->getBodyParams();
        $startDate = DateTimeHelper::toDateTime($params['startDate']);
        $endDate = DateTimeHelper::toDateTime($params['endDate']);

        if ($startDate > $endDate) {
            Craft::$app->getSession()->setError(
                'Start Date must be before End Date.'
            );
            return $this->renderTemplate(
                Plugin::HANDLE . '/manual-sync/_index',
            );
        }

        $productIds = $this->manualSyncService->fetchProductIds($startDate, $endDate);

        $queue->push(new SyncProductsJob([
            'productIds' => $productIds,
        ]));

        Craft::$app->getSession()->setNotice('Product sync queued successfully.');

        return $this->redirectToPostedUrl();
    }

    /**
     * @throws ForbiddenHttpException
     * @throws MissingComponentException
     * @throws BadRequestHttpException|InvalidConfigException
     * @throws \Exception
     */
    public function actionSyncOrders(): ?Response
    {
        $this->requirePostRequest();
        $queue = Craft::$app->getQueue();

        $params = Craft::$app->getRequest()->getBodyParams();
        $startDate = DateTimeHelper::toDateTime($params['startDate']);
        $endDate = DateTimeHelper::toDateTime($params['endDate']);

        if ($startDate > $endDate) {
            Craft::$app->getSession()->setError(
                'Start Date must be before End Date.'
            );
            return $this->renderTemplate(
                Plugin::HANDLE . '/manual-sync/_index',
            );
        }

        $orderIds = $this->manualSyncService->fetchOrderIds($startDate, $endDate);

        $queue->push(new SyncDealsJob([
            'orderIds' => $orderIds,
        ]));

        Craft::$app->getSession()->setNotice('Order sync queued successfully.');

        return $this->redirectToPostedUrl();
    }

    /**
     * @throws MissingComponentException
     * @throws BadRequestHttpException|InvalidConfigException
     * @throws \Exception
     */
    public function actionSyncCustomers(): ?Response
    {
        $this->requirePostRequest();
        $queue = Craft::$app->getQueue();

        $params = Craft::$app->getRequest()->getBodyParams();
        $startDate = DateTimeHelper::toDateTime($params['startDate']);
        $endDate = DateTimeHelper::toDateTime($params['endDate']);

        if ($startDate > $endDate) {
            Craft::$app->getSession()->setError(
                'Start Date must be before End Date.'
            );
            return $this->renderTemplate(
                Plugin::HANDLE . '/manual-sync/_index',
            );
        }

        $customerIds = $this->manualSyncService->fetchUserIds($startDate, $endDate);

        $queue->push(new SyncContactsJob([
            'customerIds' => $customerIds,
        ]));

        Craft::$app->getSession()->setNotice('Customer sync queued successfully.');

        return $this->redirectToPostedUrl();
    }

    /**
     * @throws MissingComponentException
     * @throws BadRequestHttpException|InvalidConfigException
     * @throws \Exception
     */
    public function actionSyncAll(): ?Response
    {
        $this->requirePostRequest();
        $queue = Craft::$app->getQueue();

        $params = Craft::$app->getRequest()->getBodyParams();
        $startDate = DateTimeHelper::toDateTime($params['startDate']);
        $endDate = DateTimeHelper::toDateTime($params['endDate']);

        if ($startDate > $endDate) {
            Craft::$app->getSession()->setError(
                'Start Date must be before End Date.'
            );
            return $this->renderTemplate(
                Plugin::HANDLE . '/manual-sync/_index',
            );
        }

        $productIds = $this->manualSyncService->fetchProductIds($startDate, $endDate);
        $orderIds = $this->manualSyncService->fetchOrderIds($startDate, $endDate);
        $customerIds = $this->manualSyncService->fetchUserIds($startDate, $endDate);

        $queue->push(new SyncProductsJob([
            'productIds' => $productIds,
        ]));
        $queue->push(new SyncDealsJob([
            'orderIds' => $orderIds,
        ]));
        $queue->push(new SyncContactsJob([
            'customerIds' => $customerIds,
        ]));

        Craft::$app->getSession()->setNotice('All sync queued successfully.');

        return $this->redirectToPostedUrl();
    }
}
