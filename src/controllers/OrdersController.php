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
     */
    public function actionSaveSettings(): ?Response
    {
        $this->requirePostRequest();

        $data = $this->request->getBodyParams();

        $orderSettings = new OrderSettings();
        $orderSettings->attributes = $data;
        $orderSettings->orderStages = $data['orderStages'] ?? [];

        if (! $orderSettings->validate()) {
            return $this->_redirectError($orderSettings, $orderSettings->getErrors());
        }

        $savedDb = Plugin::getInstance()->getSettingsService()->saveDb($orderSettings, HubSpotObjectTypes::DEAL);

        if (!$savedDb) {
            return $this->_redirectError($orderSettings, $orderSettings->getErrors());
        }

        try {
            // Create the unique Craft Order Identifier within Hubspot
            $hubspot = Plugin::getInstance()->getHubSpot();
            $hubspot->dealProperties()->create([
                "name" => "craft_order_number",
                "label" => "Craft Order Number",
                "description" => "The unique identifier for this order within Craft CMS",
                "groupName" => "dealinformation",
                "type" => "string",
                "fieldType" => "text",
                "readOnlyDefinition" => true,
                "hasUniqueValue" => true
            ]);
        } catch (\Exception $e) {
            Craft::error("Could not save the Craft Order Number field in Hubspot" . $e->getMessage(), Plugin::HANDLE);
        }

        $this->setSuccessFlash('Order settings saved.');

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
