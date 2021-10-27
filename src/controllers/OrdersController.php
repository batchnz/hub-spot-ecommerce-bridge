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

use batchnz\hubspotecommercebridge\enums\HubSpotDataTypes;
use batchnz\hubspotecommercebridge\enums\HubSpotObjectTypes;
use batchnz\hubspotecommercebridge\models\OrderSettings;
use batchnz\hubspotecommercebridge\Plugin;
use batchnz\hubspotecommercebridge\records\HubspotCommerceObject;
use Craft;
use craft\web\Controller;

use SevenShores\Hubspot\Factory as HubSpotFactory;
use yii\base\Exception;
use yii\web\HttpException;
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
     */
    public function actionEdit(): Response
    {
        $hubspotObject = HubspotCommerceObject::findOne(['objectType' => HubSpotObjectTypes::DEAL]);

        $orderSettings = OrderSettings::fromHubspotObject($hubspotObject);

        $variables = [
            'orderSettings' => $orderSettings
        ];

        return $this->renderTemplate(Plugin::HANDLE . '/mappings/orders/_index', $variables);
    }

    /**
     * @return Response|null
     */
    public function actionSaveSettings()
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

        $hubspotObject = HubspotCommerceObject::findOne(['objectType' => HubSpotObjectTypes::DEAL]);

        $hubspotObject->settings = $orderSettings->attributes;

        if (! $hubspotObject->validate()) {
            return $this->_redirectError($orderSettings, $orderSettings->getErrors());
        }

        $hubspotObject->save();

        $hubspotApi = Plugin::getInstance()->getHubSpot();

        $mappingService = Plugin::getInstance()->getMapping();
        $settingsUpsert = $mappingService->createSettings();

        $apiSettings = $hubspotApi->ecommerceBridge()->upsertSettings($settingsUpsert);

        //TODO more advanced checks to see if the settings went through
        if ($apiSettings->mappings) {
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
    ) {
        Craft::error(
            'Failed to save Order settings with validation errors: '
            . json_encode($errors)
        );

        $this->setFailFlash('Couldn’t save Order settings.');

        Craft::$app
            ->getUrlManager()
            ->setRouteParams(['orderSettings' => $orderSettings]);

        return null;
    }
}
