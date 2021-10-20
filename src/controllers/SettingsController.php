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

    /**
     * Creates the store in HubSpot that will sync with the craft commerce store
     *
     * @return Response
     * @throws \SevenShores\Hubspot\Exceptions\BadRequest
     */
    public function actionCreateStore(): Response
    {
        $store = [
          "id" => Plugin::STORE_ID,
          "label" => Plugin::STORE_LABEL,
          "adminUri" => Plugin::STORE_ADMIN_URI,
        ];

        $hubspot = HubSpotFactory::create(Plugin::HUBSPOT_API_KEY);
        $created = $hubspot->ecommerceBridge()->createOrUpdateStore($store);

        return $this->asJson($created);
    }

    /**
    * Uses the HubSpot SDK to create of update the settings related to the data mappings
    * @return Response
    */
    public function actionUpsertSettings(): Response
    {
        $hubspot = HubSpotFactory::create(Plugin::HUBSPOT_API_KEY);

        $settings = $this->createSettings();

        $upserted = $hubspot->ecommerceBridge()->upsertSettings($settings);

        return $this->asJson($upserted);
    }


    /**
     * Creates the mappings settings for Craft Coomerce objects to HubSpot objects
     * @return array
     */
    public function createSettings() : array
    {
        $mappingService = Plugin::getInstance()->getMapping();

        return ([
            "enabled" => true,
            "webhookUri" => Plugin::WEBHOOK_URI,
            "mappings" => [
                HubSpotObjectTypes::CONTACT =>
                    $mappingService->createObjectMapping([
                        $mappingService->createPropertyMapping("userId", "hs_object_id", HubSpotDataTypes::STRING),
                        $mappingService->createPropertyMapping("email", "email", HubSpotDataTypes::STRING),
                    ]),


                HubSpotObjectTypes::DEAL =>
                    $mappingService->createObjectMapping([
                        $mappingService->createPropertyMapping("id", "hs_object_id", HubSpotDataTypes::STRING),
                        $mappingService->createPropertyMapping("totalPrice", "amount", HubSpotDataTypes::STRING),
                        $mappingService->createPropertyMapping("dateOrdered", "createdate", HubSpotDataTypes::DATETIME),
                        $mappingService->createPropertyMapping("orderStage", "dealstage", HubSpotDataTypes::STRING),
                    ]),


                HubSpotObjectTypes::PRODUCT =>
                    $mappingService->createObjectMapping([
                        $mappingService->createPropertyMapping("defaultPrice", "price", HubSpotDataTypes::NUMBER),
                        $mappingService->createPropertyMapping("defaultSku", "hs_sku", HubSpotDataTypes::STRING),
                        $mappingService->createPropertyMapping("title", "name", HubSpotDataTypes::STRING),
                    ]),


                HubSpotObjectTypes::LINE_ITEM =>
                    $mappingService->createObjectMapping([
                        $mappingService->createPropertyMapping("id", "hs_object_id", HubSpotDataTypes::NUMBER),
                        $mappingService->createPropertyMapping("description", "description", HubSpotDataTypes::STRING),
                        $mappingService->createPropertyMapping("sku", "hs_sku", HubSpotDataTypes::STRING),
                    ]),
            ]
        ]);
    }
}
