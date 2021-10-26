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
use batchnz\hubspotecommercebridge\models\CustomerSettings;
use batchnz\hubspotecommercebridge\Plugin;
use batchnz\hubspotecommercebridge\records\HubspotCommerceObject;
use Craft;
use craft\web\Controller;

use SevenShores\Hubspot\Factory as HubSpotFactory;
use yii\base\Exception;
use yii\web\HttpException;
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
        $hubspotObject = HubspotCommerceObject::findOne(['objectType' => HubSpotObjectTypes::CONTACT]);

        $customerSettings = CustomerSettings::fromHubspotObject($hubspotObject);

        $variables = [
            'customerSettings' => $customerSettings
        ];

        return $this->renderTemplate(Plugin::HANDLE . '/mappings/customers/_index', $variables);
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

        $customerSettings = new CustomerSettings();
        $customerSettings->attributes = $data;

        if (! $customerSettings->validate()) {
            return $this->_redirectError($customerSettings, $customerSettings->getErrors());
        }

        $hubspotObject = HubspotCommerceObject::findOne(['objectType' => HubSpotObjectTypes::CONTACT]);

        $hubspotObject->settings = $customerSettings->attributes;

        if (! $hubspotObject->validate()) {
            return $this->_redirectError($customerSettings, $hubspotObject->getErrors());
        }

        $hubspotObject->save();

        $this->setSuccessFlash('Customer settings saved.');
        return $this->redirectToPostedUrl();
    }

    /**
     * Handles controller save errors
     *
     * @param CustomerSettings $customerSettings Customer Settings model
     *
     * @return void
     */
    private function _redirectError(
        CustomerSettings $customerSettings,
        array $errors = []
    ) {
        Craft::error(
            'Failed to save Customer settings with validation errors: '
            . json_encode($errors)
        );

        $this->setFailFlash('Couldn’t save Customer settings.');

        Craft::$app
            ->getUrlManager()
            ->setRouteParams(['customerSettings' => $customerSettings]);

        return null;
    }
}
