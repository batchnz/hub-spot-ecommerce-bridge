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


use batchnz\hubspotecommercebridge\listeners\ProductListener;
use batchnz\hubspotecommercebridge\listeners\VariantListener;
use batchnz\hubspotecommercebridge\services\ImportService;
use batchnz\hubspotecommercebridge\services\MappingService;
use Craft;
use craft\base\Element;
use craft\base\Plugin as CraftPlugin;
use craft\commerce\elements\Order;

use craft\commerce\elements\Variant;
use modules\core\services\PalettesService;
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

    public const HUBSPOT_API_KEY = "a9691424-5a0c-4451-81b2-8f2f7e4300bc";

    public const WEBHOOK_URI = null;


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
    public $hasCpSettings = false;

    /**
     * Set to `true` if the plugin should have its own section (main nav item) in the control panel.
     *
     * @var bool
     */
    public $hasCpSection = false;

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

        $this->registerEvents();
        $this->registerPluginComponents();

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

    // Protected Methods
    // =========================================================================

    /**
     * Registers all of the events to handle
     */
    protected function registerEvents(): void
    {
        Event::on(
            Variant::class,
            Element::EVENT_AFTER_SAVE,
            [ProductListener::class, 'upsert']
        );

        Event::on(
            Variant::class,
            Element::EVENT_AFTER_DELETE,
            [ProductListener::class, 'delete']
        );
    }

    protected function registerPluginComponents(): void
    {
        $this->setComponents([
            'mapping' => MappingService::class,
            'import' => ImportService::class,
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

}
