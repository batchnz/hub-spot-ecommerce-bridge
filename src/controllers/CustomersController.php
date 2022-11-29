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

use batchnz\hubspotecommercebridge\enums\HubSpotObjectTypes;
use batchnz\hubspotecommercebridge\models\CustomerSettings;
use batchnz\hubspotecommercebridge\Plugin;
use batchnz\hubspotecommercebridge\records\HubspotCommerceObject;
use Craft;
use craft\web\Controller;
use yii\base\Exception;
use yii\web\Response;

/**
 * Customers Controller
 *
 *
 * https://craftcms.com/docs/plugins/controllers
 *
 * @author    Daniel Siemers
 * @package   HubspotEcommerceBridge
 * @since     1.0.0
 */
class CustomersController extends Controller
{
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
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\web\ForbiddenHttpException
     */
    public function init(): void
    {
        $this->requirePermission('accessCp');
        parent::init();
    }

    /**
     * Creates the store in HubSpot that will sync with the craft commerce store
     * @return Response
     * @throws \yii\base\Exception
     */
    public function actionEdit(): Response
    {
        $hubspotObject = HubspotCommerceObject::findOne(['objectType' => HubSpotObjectTypes::CONTACT]);

        try {
            if (!$hubspotObject) {
                throw new Exception();
            }
            $customerSettings = CustomerSettings::fromHubspotObject($hubspotObject);
        } catch (Exception $e) {
            Craft::error('Could not get Customer settings from CONTACT HubSpot Object.');
            $customerSettings = new CustomerSettings();
        }

        $variables = [
            'customerSettings' => $customerSettings
        ];

        return $this->renderTemplate(Plugin::HANDLE . '/mappings/customers/_index', $variables);
    }

    /**
     * @return Response|null|void
     * @throws \yii\web\BadRequestHttpException
     * @throws \yii\base\InvalidConfigException
     * @throws \SevenShores\Hubspot\Exceptions\BadRequest
     */
    public function actionSaveSettings(): ?Response
    {
        $this->requirePostRequest();

        $data = $this->request->getBodyParams();

        $customerSettings = new CustomerSettings();
        $customerSettings->attributes = $data;
        if (! $customerSettings->validate()) {
            return $this->_redirectError($customerSettings, $customerSettings->getErrors());
        }

        $savedDb = Plugin::getInstance()->getSettingsService()->saveDb($customerSettings, HubSpotObjectTypes::CONTACT);

        if (!$savedDb) {
            return $this->_redirectError($customerSettings, $customerSettings->getErrors());
        }

        $this->setSuccessFlash('Customer settings saved.');

        return $this->redirectToPostedUrl();
    }

    /**
     * Handles controller save errors
     *
     * @param CustomerSettings $customerSettings Customer Settings model
     *
     * @return void
     * @throws \JsonException
     */
    private function _redirectError(
        CustomerSettings $customerSettings,
        array $errors = []
    ): void {
        Craft::error(
            'Failed to save Customer settings with validation errors: '
            . json_encode($errors, JSON_THROW_ON_ERROR)
        );

        $this->setFailFlash('Couldn’t save Customer settings.');

        Craft::$app
            ->getUrlManager()
            ->setRouteParams(['customerSettings' => $customerSettings]);
    }
}
