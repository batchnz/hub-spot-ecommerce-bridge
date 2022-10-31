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
use batchnz\hubspotecommercebridge\models\ProductSettings;
use batchnz\hubspotecommercebridge\Plugin;
use batchnz\hubspotecommercebridge\records\HubspotCommerceObject;
use Craft;
use craft\web\Controller;

use yii\base\Exception;
use yii\web\Response;

/**
 * Products Controller
 *
 *
 * @author    Daniel Siemers
 * @package   HubspotEcommerceBridge
 * @since     1.0.0
 */
class ProductsController extends Controller
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

    public function init(): void
    {
        $this->requirePermission('accessCp');
        parent::init();
    }

    /**
     * Creates the store in HubSpot that will sync with the craft commerce store
     * @return Response
     * @throws Exception
     */
    public function actionEdit(): Response
    {
        $hubspotObject = HubspotCommerceObject::findOne(['objectType' => HubSpotObjectTypes::PRODUCT]);

        try {
            if (!$hubspotObject) {
                throw new Exception();
            }
            $productSettings = ProductSettings::fromHubspotObject($hubspotObject);
        } catch (Exception $e) {
            Craft::error('Could not get Product Item settings from PRODUCT HubSpot Object.');
            $productSettings = new ProductSettings();
        }

        $variables = [
            'productSettings' => $productSettings
        ];

        return $this->renderTemplate(Plugin::HANDLE . '/mappings/products/_index', $variables);
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

        $productSettings = new ProductSettings();
        $productSettings->attributes = $data;

        if (! $productSettings->validate()) {
            return $this->_redirectError($productSettings, $productSettings->getErrors());
        }

        $savedDb = Plugin::getInstance()->getSettingsService()->saveDb($productSettings, HubSpotObjectTypes::PRODUCT);

        if (!$savedDb) {
            return $this->_redirectError($productSettings, $productSettings->getErrors());
        }

        $savedApi = Plugin::getInstance()->getSettingsService()->saveApi();

        if ($savedApi) {
            $this->setSuccessFlash('Product settings saved.');
        } else {
            $this->setFailFlash('Error while connecting to the HubSpot API.');
        }

        return $this->redirectToPostedUrl();
    }

    /**
     * Handles controller save errors
     *
     * @param ProductSettings $productSettings Product Settings model
     *
     * @return void
     * @throws \JsonException
     */
    private function _redirectError(
        ProductSettings $productSettings,
        array $errors = []
    ): void {
        Craft::error(
            'Failed to save Product settings with validation errors: '
            . json_encode($errors, JSON_THROW_ON_ERROR)
        );

        $this->setFailFlash('Couldn’t save Product settings.');

        Craft::$app
            ->getUrlManager()
            ->setRouteParams(['productSettings' => $productSettings]);
    }
}
