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
 * Settings Controller
 *
 * Generally speaking, controllers are the middlemen between the front end of
 * the CP/website and your plugin’s services. They contain action methods which
 * handle individual tasks.
 *
 * A common pattern used throughout Craft involves a controller action gathering
 * post data, saving it on a model, passing the model off to a service, and then
 * responding to the request appropriately depending on the service method’s response.
 *
 * Action methods begin with the prefix “action”, followed by a description of what
 * the method does (for example, actionSaveIngredient()).
 *
 * https://craftcms.com/docs/plugins/controllers
 *
 * @author    Daniel Siemers
 * @package   HubspotEcommerceBridge
 * @since     1.0.0
 */
class SettingsController extends Controller
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

        return $this->renderTemplate(Plugin::HANDLE . '/settings/_index', $variables);
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

    /**
     * Creates the store in HubSpot that will sync with the craft commerce store
     *     * @return Response
     * @throws \SevenShores\Hubspot\Exceptions\BadRequest
     */
    public function actionCreateStore(): Response
    {
        $store = [
          "id" => Plugin::getInstance()->getSettings()->storeId,
          "label" => Plugin::getInstance()->getSettings()->storeLabel,
          "adminUri" => Plugin::getInstance()->getSettings()->storeAdminUri,
        ];

        $hubspot = Plugin::getInstance()->getHubSpot();
        $created = $hubspot->ecommerceBridge()->createOrUpdateStore($store);

        return $this->asJson($created);
    }

    /**
    * Uses the HubSpot SDK to create of update the settings related to the data mappings
    * @return Response
    */
    public function actionUpsertSettings(): Response
    {
        $mappingService = Plugin::getInstance()->getMapping();

        $settings = $mappingService->createSettings();

        $hubspot = Plugin::getInstance()->getHubSpot();
        $upserted = $hubspot->ecommerceBridge()->upsertSettings($settings);

        return $this->asJson($settings);
    }

    /**
     * Uses the HubSpot SDK to create of update the settings related to the data mappings
     * @return Response
     */
    public function actionGetSettings(): Response
    {
        $mappingService = Plugin::getInstance()->getMapping();

        $hubspot = Plugin::getInstance()->getHubSpot();
        $settings = $hubspot->ecommerceBridge()->getSettings();

        return $this->asJson($settings);
    }
}
