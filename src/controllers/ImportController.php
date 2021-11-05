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
use batchnz\hubspotecommercebridge\Plugin;

use Craft;
use craft\web\Controller;
use yii\web\Response;

/**
 * ImportData Controller
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
class ImportController extends Controller
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

    /**
     * @throws \yii\web\ForbiddenHttpException
     * @throws \yii\base\InvalidConfigException
     */
    public function init()
    {
        $this->requirePermission('accessCp');
        parent::init();
    }

    /**
     * Handles incoming request to import all data
     */
    public function actionIndex(): Response
    {
        $importService = Plugin::getInstance()->getImport();

        $imported = $importService->importAll();

        return $this->asJson($imported);
    }

    /**
     * Handles incoming request to delete all data
     */
    public function actionDeleteAll(): Response
    {
        $importService = Plugin::getInstance()->getImport();

        $deleted = $importService->deleteAll();

        return $this->asJson($deleted);
    }

    public function actionCheckSync(): Response
    {
        $hubspot = Plugin::getInstance()->getHubSpot();

        $res = $hubspot->ecommerceBridge()->checkSyncStatus(Plugin::getInstance()->getSettings()->storeId, HubSpotObjectTypes::DEAL, 35933);

        return $this->asJson($res);
    }
}
