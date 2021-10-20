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

use batchnz\hubspotecommercebridge\Plugin;

use Craft;
use craft\commerce\elements\Order;
use craft\commerce\elements\Product;
use craft\commerce\models\Customer;
use craft\commerce\services\Customers;
use craft\web\Controller;
use SevenShores\Hubspot\Factory as HubSpotFactory;

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
     * Handle the incoming request from the import webhook and
     * return the counts of the objects to be imported.
     */
    public function actionIndex()
    {
        $request = Craft::$app->getRequest();
        return $this->asJson($request);
    }


    /**
     * Handle a request going to our plugin's index action URL,
     * e.g.: actions/hub-spot-ecommerce-bridge/import
     *
     * @return mixed
     */
    public function actionTemp()
    {

//        $orders = Order::findAll();
//        $customers = new Customers();
//        $customers = $customers->getAllCustomers();

        //TODO make it send in batches of 200
        $products = Product::findAll();

        $productMessages = array_map(function ($product) {
            $milliseconds = round(microtime(true) * 1000);
            return (
                [
                    "action" => "UPSERT",
                    "changedAt" => $milliseconds,
                    "externalObjectId" => $product->defaultSku,
                    "properties" => [
                        "defaultPrice" => $product->defaultPrice,
                        "defaultSku" => $product->defaultSku,
                        "title" => $product->title,
                    ]
                ]
            );
        }, $products);

        $hubspot = HubSpotFactory::create(Plugin::HUBSPOT_API_KEY);

        $success = $hubspot->ecommerceBridge()->sendSyncMessages(Plugin::STORE_ID, "PRODUCT", $productMessages);

        return $this->asJson($productMessages);
    }
}
