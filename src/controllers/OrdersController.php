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
use batchnz\hubspotecommercebridge\models\OrderSettings;
use batchnz\hubspotecommercebridge\Plugin;
use batchnz\hubspotecommercebridge\records\HubspotCommerceObject;
use Craft;
use craft\web\Controller;

use yii\base\Exception;
use yii\web\Response;

/**
 * Orders Controller
 *
 *
 * https://craftcms.com/docs/plugins/controllers
 *
 * @author    Daniel Siemers
 * @package   HubspotEcommerceBridge
 * @since     1.0.0
 */
class OrdersController extends Controller
{
    // Protected Properties
    // =========================================================================

    /**
    * @var    bool|array Allows anonymous access to this controller's actions.
    *         The actions must be in 'kebab-case'
    * @access protected
    */
    protected $allowAnonymous = true;

    // Public Methods
    // =========================================================================

    public function init()
    {
        $this->requireAdmin();
        parent::init();
    }

    /**
     * Creates the store in HubSpot that will sync with the craft commerce store
     * @return Response
     * @throws Exception
     */
    public function actionEdit(): Response
    {
        $hubspotObject = HubspotCommerceObject::findOne(['objectType' => HubSpotObjectTypes::DEAL]);

        try {
            if (!$hubspotObject) {
                throw new Exception();
            }
            $orderSettings = OrderSettings::fromHubspotObject($hubspotObject);
        } catch (Exception $e) {
            Craft::error('Could not get Order Item settings from DEAL HubSpot Object.');
            $orderSettings = new OrderSettings();
        }

        $variables = [
            'orderSettings' => $orderSettings
        ];

        return $this->renderTemplate(Plugin::HANDLE . '/mappings/orders/_index', $variables);
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

//        // Connection ID is a required parameter
//        if (!empty($data['firstname']) ) {
//            $this->setFailFlash(Plugin::t('Couldn\'t find the organisation\'s connection.'));
//            return null;
//        }

        $orderSettings = new OrderSettings();
        $orderSettings->attributes = $data;

        if (! $orderSettings->validate()) {
            return $this->_redirectError($orderSettings, $orderSettings->getErrors());
        }

        $savedDb = Plugin::getInstance()->getSettingsService()->saveDb($orderSettings, HubSpotObjectTypes::DEAL);

        if (!$savedDb) {
            return $this->_redirectError($orderSettings, $orderSettings->getErrors());
        }

        $savedApi = Plugin::getInstance()->getSettingsService()->saveApi();

        if ($savedApi) {
            $this->setSuccessFlash('Order settings saved.');
        } else {
            $this->setFailFlash('Error while connecting to the HubSpot API.');
        }

        return $this->redirectToPostedUrl();
    }

    /**
     * Handles controller save errors
     *
     * @param OrderSettings $orderSettings Order Settings model
     *
     * @return void
     */
    private function _redirectError(
        OrderSettings $orderSettings,
        array $errors = []
    ): void {
        Craft::error(
            'Failed to save Order settings with validation errors: '
            . json_encode($errors, JSON_THROW_ON_ERROR)
        );

        $this->setFailFlash('Couldn’t save Order settings.');

        Craft::$app
            ->getUrlManager()
            ->setRouteParams(['orderSettings' => $orderSettings]);
    }
}
