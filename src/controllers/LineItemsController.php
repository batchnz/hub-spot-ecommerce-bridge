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
use batchnz\hubspotecommercebridge\Plugin;
use Craft;
use craft\web\Controller;

use SevenShores\Hubspot\Factory as HubSpotFactory;
use yii\base\Exception;
use yii\web\HttpException;
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
        $settings = Plugin::getInstance()->getSettings();

        $variables = [
            'settings' => $settings
        ];

        return $this->renderTemplate(Plugin::HANDLE . '/mappings/line-items/_index', $variables);
    }

    /**
     * @return Response|null
     */
    public function actionSaveSettings()
    {
        $this->requirePostRequest();

        $params = Craft::$app->getRequest()->getBodyParams();
        $data = $params['settings'];

        $settings = Plugin::getInstance()->getSettings();
        $settings->apiKey = $data['apiKey'] ?? $settings->apiKey;
        $settings->storeId = $data['storeId'] ?? $settings->storeId;
        $settings->storeLabel = $data['storeLabel'] ?? $settings->storeLabel;
        $settings->storeAdminUri = $data['storeAdminUri'] ?? $settings->storeAdminUri;

        if (!$settings->validate()) {
            Craft::$app->getSession()->setError(
                'Couldn’t save settings.'
            );
            return $this->renderTemplate(
                Plugin::HANDLE . '/settings/_index', compact('settings')
            );
        }

        $pluginSettingsSaved = Craft::$app->getPlugins()->savePluginSettings(
            Plugin::getInstance(), $settings->toArray()
        );

        if (!$pluginSettingsSaved) {
            Craft::$app->getSession()->setError(
                'Couldn’t save settings.'
            );
            return $this->renderTemplate(
                Plugin::HANDLE . '/settings/_index', compact('settings')
            );
        }

        Craft::$app->getSession()->setNotice('Settings saved.');

        return $this->redirectToPostedUrl();
    }
}
