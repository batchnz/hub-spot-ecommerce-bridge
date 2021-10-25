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


use batchnz\hubspotecommercebridge\listeners\LineItemListener;
use batchnz\hubspotecommercebridge\listeners\ProductListener;
use batchnz\hubspotecommercebridge\listeners\VariantListener;
use batchnz\hubspotecommercebridge\services\ImportService;
use batchnz\hubspotecommercebridge\services\MappingService;
use batchnz\hubspotecommercebridge\models\Settings;
use Craft;
use craft\base\Element;
use craft\base\Plugin as CraftPlugin;
use craft\commerce\elements\Order;

use craft\commerce\elements\Variant;
use craft\commerce\services\LineItems;
use craft\events\RegisterCpNavItemsEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\helpers\UrlHelper;
use craft\web\twig\variables\Cp;
use craft\web\UrlManager;
use modules\core\services\PalettesService;
use SevenShores\Hubspot\Factory as HubSpotFactory;
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
    public const STORE_ID = "craft-commerce-bridge";
    public const STORE_LABEL = "Craft Commerce Bridge";
    public const STORE_ADMIN_URI = "https://naturalpaint.co.nz/admin";

    public const WEBHOOK_URI = null;


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
    public $schemaVersion = '1.0.0';

    /**
     * Set to `true` if the plugin should have a settings view in the control panel.
     *
     * @var bool
     */
    public $hasCpSettings = true;

    /**
     * Set to `true` if the plugin should have its own section (main nav item) in the control panel.
     *
     * @var bool
     */
    public $hasCpSection = true;

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

        if (true) {
            $ret['subnav']['settings'] = [
                'label' => 'Settings',
                'url' => self::HANDLE . '/settings'
            ];
        }

        return $ret;
    }

    public function getSettingsResponse()
    {
        Craft::$app->controller->redirect(UrlHelper::cpUrl(self::HANDLE . '/settings'));
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

        // On add LineItem to Order
        Event::on(
            LineItems::class,
            LineItems::EVENT_AFTER_SAVE_LINE_ITEM,
            [LineItemListener::class, 'upsert'],
        );

        //On remove LineItem from Order
        Event::on(
            Order::class,
            Order::EVENT_AFTER_APPLY_REMOVE_LINE_ITEM,
            [LineItemListener::class, 'delete'],
        );
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

        // Create an preconfigured instance of the HubSpot provider
        // to be injected into each instance of the api service
        $hubspot = HubSpotFactory::create($settings->apiKey);

        $this->setComponents([
            'mapping' => MappingService::class,
            'import' => ImportService::class,
            'hubspot' => $hubspot,
        ]);
    }

    /**
     * Returns the mapping service
     *
     * @return MappingService
     */
    public function getMapping(): MappingService
    {
        return $this->get('mapping');
    }

    /**
     * Returns the import service
     *
     * @return ImportService
     */
    public function getImport(): ImportService
    {
        return $this->get('import');
    }

    /**
     * Returns the HubSpot provider
     *
     * @return HubSpotFactory
     */
    public function getHubSpot(): HubSpotFactory
    {
        return $this->get('hubspot');
    }



    protected function createSettingsModel()
    {
        return new Settings();
    }

}
