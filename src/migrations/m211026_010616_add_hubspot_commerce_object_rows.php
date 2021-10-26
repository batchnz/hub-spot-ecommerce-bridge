<?php

namespace batchnz\hubspotecommercebridge\migrations;

use batchnz\hubspotecommercebridge\enums\HubSpotObjectTypes;
use batchnz\hubspotecommercebridge\models\CustomerSettings;
use batchnz\hubspotecommercebridge\models\OrderSettings;
use batchnz\hubspotecommercebridge\models\ProductSettings;
use batchnz\hubspotecommercebridge\models\LineItemSettings;
use batchnz\hubspotecommercebridge\records\HubspotCommerceObject;
use Craft;
use craft\db\Migration;

/**
 * m211026_010616_add_hubspot_commerce_object_rows migration.
 */
class m211026_010616_add_hubspot_commerce_object_rows extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $customer = new CustomerSettings();
        $this->insert(HubspotCommerceObject::tableName(), ['objectType' => HubSpotObjectTypes::CONTACT, "settings" => json_encode($customer)]);
        $order = new OrderSettings();
        $this->insert(HubspotCommerceObject::tableName(), ['objectType' => HubSpotObjectTypes::DEAL, "settings" => json_encode($order)]);
        $product = new ProductSettings();
        $this->insert(HubspotCommerceObject::tableName(), ['objectType' => HubSpotObjectTypes::PRODUCT, "settings" => json_encode($product)]);
        $lineItem = new LineItemSettings();
        $this->insert(HubspotCommerceObject::tableName(), ['objectType' => HubSpotObjectTypes::LINE_ITEM, "settings" => json_encode($lineItem)]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->truncateTable(HubspotCommerceObject::tableName());
    }
}
