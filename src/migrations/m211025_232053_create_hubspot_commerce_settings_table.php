<?php

namespace batchnz\hubspotecommercebridge\migrations;

use Craft;
use craft\db\Migration;
use batchnz\hubspotecommercebridge\records\HubspotCommerceObject;

/**
 * m211025_232053_create_hubspot_commerce_settings_table migration.
 */
class m211025_232053_create_hubspot_commerce_settings_table extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        if (!$this->_tableExists(HubspotCommerceObject::tableName())) {
            $this->createTable(
                HubspotCommerceObject::tableName(), [
                    'id' => $this->primaryKey(),
                    'objectType' => $this->string(40)->notNull(),
                    'settings' => $this->longText()->notNull(),
                    'dateCreated' => $this->dateTime()->notNull(),
                    'dateUpdated' => $this->dateTime()->notNull(),
                    'uid' => $this->uid(),
                ]
            );
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
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
