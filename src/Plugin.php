<?php

/**
 * HubSpot Ecommerce Bridge plugin for Craft CMS 3.x
 *
 * Uses the HubSpot Ecommerce Bridge to sync data from Craft Commerce
 *
 * @link      https://www.batch.nz/
 * @copyright Copyright (c) 2021 Daniel Siemers
 */

namespace batchnz\hubspotecommercebridge;

use batchnz\hubspotecommercebridge\listeners\CustomerListener;
use batchnz\hubspotecommercebridge\listeners\ProductListener;
use batchnz\hubspotecommercebridge\services\CustomerService;
use batchnz\hubspotecommercebridge\services\LineItemService;
use batchnz\hubspotecommercebridge\services\ManualSyncService;
use batchnz\hubspotecommercebridge\services\OrderService;
use batchnz\hubspotecommercebridge\services\ProductService;
use batchnz\hubspotecommercebridge\models\Settings;
use batchnz\hubspotecommercebridge\services\SettingsService;
use Craft;
use craft\base\Element;
use craft\base\Plugin as CraftPlugin;
use craft\commerce\elements\Order;
use craft\commerce\elements\Variant;
use craft\elements\User;
use craft\events\RegisterUrlRulesEvent;
use craft\helpers\App;
use craft\helpers\UrlHelper;
use craft\web\UrlManager;
use HubSpot\Discovery\Discovery;
use yii\base\Event;

use batchnz\hubspotecommercebridge\listeners\OrderListener;

/**
 * Craft plugins are very much like little applications in and of themselves. We’ve made
 * it as simple as we can, but the training wheels are off. A little prior knowledge is
 * going to be required to write a plugin.
 *
 * For the purposes of the plugin docs, we’re going to assume that you know PHP and SQL,
 * as well as some semi-advanced concepts like object-oriented programming and PHP namespaces.
 *
 * https://docs.craftcms.com/v3/extend/
 *
 * @author    Daniel Siemers
 * @package   HubspotEcommerceBridge
 * @since     1.0.0
 *
 */
class Plugin extends CraftPlugin
{
    public const HANDLE = "hub-spot-ecommerce-bridge";

    // Static Properties
    // =========================================================================

    /**
     * Static property that is an instance of this plugin class so that it can be accessed via
     * Plugin::$plugin
     *
     * @var Plugin
     */
    public static $plugin;

    // Public Properties
    // =========================================================================

    /**
     * To execute your plugin’s migrations, you’ll need to increase its schema version.
     *
     * @var string
     */
    public string $schemaVersion = '1.0.0';

    /**
     * Set to `true` if the plugin should have a settings view in the control panel.
     *
     * @var bool
     */
    public bool $hasCpSettings = true;

    /**
     * Set to `true` if the plugin should have its own section (main nav item) in the control panel.
     *
     * @var bool
     */
    public bool $hasCpSection = true;

    // Public Methods
    // =========================================================================

    /**
     * Set our $plugin static property to this class so that it can be accessed via
     * Plugin::$plugin
     *
     * Called after the plugin class is instantiated; do any one-time initialization
     * here such as hooks and events.
     *
     * If you have a '/vendor/autoload.php' file, it will be loaded for you automatically;
     * you do not need to load it in your init() method.
     *
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        Craft::setAlias('@batchnz\hubspotecommercebridge', $this->getBasePath());

        $this->_registerEvents();
        $this->_registerPluginComponents();
        $this->_registerCpRoutes();

        /**
         * Logging in Craft involves using one of the following methods:
         *
         * Craft::trace(): record a message to trace how a piece of code runs. This is mainly for development use.
         * Craft::info(): record a message that conveys some useful information.
         * Craft::warning(): record a warning message that indicates something unexpected has happened.
         * Craft::error(): record a fatal error that should be investigated as soon as possible.
         *
         * Unless `devMode` is on, only Craft::warning() & Craft::error() will log to `craft/storage/logs/web.log`
         *
         * It's recommended that you pass in the magic constant `__METHOD__` as the second parameter, which sets
         * the category to the method (prefixed with the fully qualified class name) where the constant appears.
         *
         * To enable the Yii debug toolbar, go to your user account in the AdminCP and check the
         * [] Show the debug toolbar on the front end & [] Show the debug toolbar on the Control Panel
         *
         * http://www.yiiframework.com/doc-2.0/guide-runtime-logging.html
         */
        Craft::info(
            Craft::t(
                'hub-spot-ecommerce-bridge',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }

    /**
     * @inheritdoc
     */
    public function getCpNavItem(): array
    {
        $ret = parent::getCpNavItem();

        $ret['label'] = 'HubSpot Commerce';
        $ret['url'] = self::HANDLE;

        if (true) {
            $ret['subnav']['mappings'] = [
                'label' => 'Mappings',
                'url' => self::HANDLE . '/mappings'
            ];
        }

        $ret['subnav']['manual-sync'] = [
            'label' => 'Manual Sync',
            'url' => self::HANDLE . '/manual-sync'
        ];

        if (Craft::$app->getUser()->getIsAdmin() && Craft::$app->getConfig()->getGeneral()->allowAdminChanges) {
            $ret['subnav']['settings'] = [
                'label' => 'Settings',
                'url' => self::HANDLE . '/settings'
            ];
        }

        return $ret;
    }

    public function getSettingsResponse(): \yii\web\Response
    {
        return Craft::$app->controller->redirect(UrlHelper::cpUrl(self::HANDLE . '/settings'));
    }

    // Protected Methods
    // =========================================================================

    /**
     * Registers all of the events to handle
     */
    protected function _registerEvents(): void
    {
        // On Product (variant) save
        Event::on(
            Variant::class,
            Element::EVENT_AFTER_SAVE,
            [ProductListener::class, 'upsert']
        );

        // On Product (variant) delete
        Event::on(
            Variant::class,
            Element::EVENT_AFTER_DELETE,
            [ProductListener::class, 'delete']
        );

        // On Order save
        Event::on(
            Order::class,
            Element::EVENT_AFTER_SAVE,
            [OrderListener::class, 'upsert']
        );

        // On Order delete
        Event::on(
            Order::class,
            Element::EVENT_AFTER_DELETE,
            [OrderListener::class, 'delete']
        );

        // On save Customer
        Event::on(
            User::class,
            Element::EVENT_AFTER_SAVE,
            [CustomerListener::class, 'upsert'],
        );

        // ALERT: No way to delete customers as of now
    }

    // Private Methods
    // =========================================================================

    /**
     * Registers custom CP routes
     *
     * @return void
     */
    protected function _registerCpRoutes()
    {
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules[Plugin::HANDLE] = Plugin::HANDLE . '/customers/edit';
                $event->rules[Plugin::HANDLE . '/settings'] = Plugin::HANDLE . '/settings/edit';
                $event->rules[Plugin::HANDLE . '/manual-sync'] = Plugin::HANDLE . '/manual-sync/edit';
                $event->rules[Plugin::HANDLE . '/mappings'] = Plugin::HANDLE . '/customers/edit';
                $event->rules[Plugin::HANDLE . '/mappings/customers'] = Plugin::HANDLE . '/customers/edit';
                $event->rules[Plugin::HANDLE . '/mappings/orders'] = Plugin::HANDLE . '/orders/edit';
                $event->rules[Plugin::HANDLE . '/mappings/products'] = Plugin::HANDLE . '/products/edit';
                $event->rules[Plugin::HANDLE . '/mappings/line-items'] = Plugin::HANDLE . '/line-items/edit';
            }
        );
    }

    protected function _registerPluginComponents(): void
    {
        $settings = $this->getSettings();

        // Create a preconfigured instance of the HubSpot provider
        // to be injected into each instance of the api service
        $hubspot = \HubSpot\Factory::createWithAccessToken(App::parseEnv($settings->apiKey));

        $this->setComponents([
            'hubspot' => $hubspot,
            'product' => ProductService::class,
            'customer' => CustomerService::class,
            'order' => OrderService::class,
            'lineItem' => LineItemService::class,
            'manualSync' => ManualSyncService::class,
            'settings' => SettingsService::class,
        ]);
    }

    /**
     * Returns the HubSpot provider
     *
     * @return Discovery
     */
    public function getHubSpot(): Discovery
    {
        return $this->get('hubspot');
    }

    /**
     * Returns the product service
     *
     * @return ProductService
     */
    public function getProduct(): ProductService
    {
        return $this->get('product');
    }

    /**
     * Returns the customer service
     *
     * @return CustomerService
     */
    public function getCustomer(): CustomerService
    {
        return $this->get('customer');
    }

    /**
     * Returns the order service
     *
     * @return OrderService
     */
    public function getOrder(): OrderService
    {
        return $this->get('order');
    }

    /**
     * Returns the lineItem service
     *
     * @return LineItemService
     */
    public function getLineItem(): LineItemService
    {
        return $this->get('lineItem');
    }

    /**
     * Returns the manualSync service
     *
     * @return ManualSyncService
     */
    public function getManualSync(): ManualSyncService
    {
        return $this->get('manualSync');
    }

    /**
     * Returns the settings service
     *
     * @return SettingsService
     */
    public function getSettingsService(): SettingsService
    {
        return $this->get('settings');
    }

    protected function createSettingsModel(): ?craft\base\Model
    {
        return new Settings();
    }
}
