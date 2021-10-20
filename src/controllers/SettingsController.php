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

use batchnz\hubspotecommercebridge\enums\HubSpotTypes;
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
    public function actionCreateStore()
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
    * This will create or update the settings of the store mapping
    * between the craft commerce store and hubspots ecommerce store.
    *
    * @return mixed
    */
    public function actionUpsertSettings(): Response
    {
        $hubspot = HubSpotFactory::create(Plugin::HUBSPOT_API_KEY);

        $settings = $this->createSettings();


        $upserted = $hubspot->ecommerceBridge()->upsertSettings($settings);

        return $this->asJson($upserted);
    }

    public function createSettings() : array
    {
        $settings = [
            "enabled" => true,
            "webhookUri" => Plugin::WEBHOOK_URI,
            "mappings" => [
                "CONTACT" => [
                    "properties" => [
                        [
                            "externalPropertyName" => "userId",
                            "hubspotPropertyName" => "hs_object_id",
                            "dataType" => HubSpotTypes::STRING,
                        ],
                        [
                            "externalPropertyName" => "email",
                            "hubspotPropertyName" => "email",
                            "dataType" => HubSpotTypes::STRING,
                        ],
                        [
                            "externalPropertyName" => "email",
                            "hubspotPropertyName" => "email",
                            "dataType" => HubSpotTypes::STRING,
                        ]
                    ]
                ],
                "DEAL" => [
                    "properties" => [
                        [
                            "externalPropertyName" => "id",
                            "hubspotPropertyName" => "hs_object_id",
                            "dataType" => HubSpotTypes::NUMBER,
                        ],
                        [
                            "externalPropertyName" => "totalPrice",
                            "hubspotPropertyName" => "amount",
                            "dataType" => HubSpotTypes::NUMBER,
                        ],
                        [
                            "externalPropertyName" => "dateOrdered",
                            "hubspotPropertyName" => "createdate",
                            "dataType" => HubSpotTypes::DATETIME,
                        ],
                        [
                            "externalPropertyName" => "orderStage",
                            "hubspotPropertyName" => "dealstage",
                            "dataType" => HubSpotTypes::STRING,
                        ],
                    ]
                ],
                "PRODUCT" => [
                    "properties" => [
                        [
                          "externalPropertyName" => "defaultPrice",
                          "hubspotPropertyName" => "price",
                          "dataType" => HubSpotTypes::NUMBER,
                        ],
                        [
                          "externalPropertyName" => "defaultSku",
                          "hubspotPropertyName" => "hs_sku",
                          "dataType" => HubSpotTypes::STRING,
                        ],
                        [
                            "externalPropertyName" => "title",
                            "hubspotPropertyName" => "name",
                            "dataType" => HubSpotTypes::STRING,
                        ],
                    ]
                ],
                "LINE_ITEM" => [
                    "properties" => [
                        [
                            "externalPropertyName" => "id",
                            "hubspotPropertyName" => "hs_object_id",
                            "dataType" => HubSpotTypes::NUMBER,
                        ],
                        [
                            "externalPropertyName" => "description",
                            "hubspotPropertyName" => "description",
                            "dataType" => HubSpotTypes::STRING,
                        ],
                        [
                            "externalPropertyName" => "sku",
                            "hubspotPropertyName" => "hs_sku",
                            "dataType" => HubSpotTypes::STRING,
                        ],
                    ]
                ]
            ]
        ];

        return $settings;
    }
}
