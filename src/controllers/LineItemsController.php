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
use batchnz\hubspotecommercebridge\models\LineItemSettings;
use batchnz\hubspotecommercebridge\Plugin;
use batchnz\hubspotecommercebridge\records\HubspotCommerceObject;
use Craft;
use craft\web\Controller;

use yii\base\Exception;
use yii\web\Response;

/**
 * LineItems Controller
 *
 *
 * https://craftcms.com/docs/plugins/controllers
 *
 * @author    Daniel Siemers
 * @package   HubspotEcommerceBridge
 * @since     1.0.0
 */
class LineItemsController extends Controller
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
     * @throws Exception
     */
    public function actionEdit(): Response
    {
        $hubspotObject = HubspotCommerceObject::findOne(['objectType' => HubSpotObjectTypes::LINE_ITEM]);

        try {
            if (!$hubspotObject) {
                throw new Exception();
            }
            $lineItemSettings = LineItemSettings::fromHubspotObject($hubspotObject);
        } catch (Exception $e) {
            Craft::error('Could not get Line Item settings from LINE_ITEM HubSpot Object.');
            $lineItemSettings = new LineItemSettings();
        }

        $variables = [
            'lineItemSettings' => $lineItemSettings
        ];

        return $this->renderTemplate(Plugin::HANDLE . '/mappings/line-items/_index', $variables);
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

        $lineItemSettings = new LineItemSettings();
        $lineItemSettings->attributes = $data;

        if (! $lineItemSettings->validate()) {
            return $this->_redirectError($lineItemSettings, $lineItemSettings->getErrors());
        }

        $savedDb = Plugin::getInstance()->getSettingsService()->saveDb($lineItemSettings, HubSpotObjectTypes::LINE_ITEM);

        if (!$savedDb) {
            return $this->_redirectError($lineItemSettings, $lineItemSettings->getErrors());
        }

        $this->setSuccessFlash('Line Item settings saved.');

        return $this->redirectToPostedUrl();
    }

    /**
     * Handles controller save errors
     *
     * @param LineItemSettings $lineItemSettings LineItem Settings model
     *
     * @return void
     * @throws \JsonException
     */
    private function _redirectError(
        LineItemSettings $lineItemSettings,
        array $errors = []
    ): void {
        Craft::error(
            'Failed to save Line Item settings with validation errors: '
            . json_encode($errors, JSON_THROW_ON_ERROR)
        );

        $this->setFailFlash('Couldn’t save Line Item settings.');

        Craft::$app
            ->getUrlManager()
            ->setRouteParams(['lineItemSettings' => $lineItemSettings]);
    }
}
