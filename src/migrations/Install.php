<?php

namespace batchnz\hubspotecommercebridge\migrations;

use batchnz\hubspotecommercebridge\enums\HubSpotObjectTypes;
use batchnz\hubspotecommercebridge\models\CustomerSettings;
use batchnz\hubspotecommercebridge\models\LineItemSettings;
use batchnz\hubspotecommercebridge\models\OrderSettings;
use batchnz\hubspotecommercebridge\models\ProductSettings;
use craft\db\Migration;
use batchnz\hubspotecommercebridge\records\HubspotCommerceObject;

/**
 * Runs all the necessary migrations on plugin install.
 */
class Install extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        if (!$this->_tableExists(HubspotCommerceObject::tableName())) {
            $this->createTable(
                HubspotCommerceObject::tableName(),
                [
                    'id' => $this->primaryKey(),
                    'objectType' => $this->string(40)->notNull(),
                    'settings' => $this->longText()->notNull(),
                    'dateCreated' => $this->dateTime()->notNull(),
                    'dateUpdated' => $this->dateTime()->notNull(),
                    'uid' => $this->uid(),
                ]
            );

            $customer = new CustomerSettings();
            $this->insert(HubspotCommerceObject::tableName(), ['objectType' => HubSpotObjectTypes::CONTACT, "settings" => json_encode($customer)]);
            $order = new OrderSettings();
            $this->insert(HubspotCommerceObject::tableName(), ['objectType' => HubSpotObjectTypes::DEAL, "settings" => json_encode($order)]);
            $product = new ProductSettings();
            $this->insert(HubspotCommerceObject::tableName(), ['objectType' => HubSpotObjectTypes::PRODUCT, "settings" => json_encode($product)]);
            $lineItem = new LineItemSettings();
            $this->insert(HubspotCommerceObject::tableName(), ['objectType' => HubSpotObjectTypes::LINE_ITEM, "settings" => json_encode($lineItem)]);
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->truncateTable(HubspotCommerceObject::tableName());
        $this->dropTable(HubspotCommerceObject::tableName());
    }

    /**
     * Returns if the table exists.
     *
     * @param  string $tableName
     * @return bool If the table exists.
     */
    private function _tableExists(string $tableName): bool
    {
        $schema = $this->db->getSchema();
        $schema->refresh();

        $rawTableName = $schema->getRawTableName($tableName);
        $table = $schema->getTableSchema($rawTableName);

        return (bool)$table;
    }
}
