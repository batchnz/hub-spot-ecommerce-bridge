<?php

namespace batchnz\hubspotecommercebridge\migrations;

use batchnz\hubspotecommercebridge\records\OrderLock;
use craft\db\Migration;

/**
 * m230811_032330_create_order_lock_table migration.
 */
class m230811_032330_create_order_lock_table extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        if (!$this->_tableExists(OrderLock::tableName())) {
            // Create the table
            $this->createTable(OrderLock::tableName(), [
                'id' => $this->primaryKey(),
                'orderId' => $this->integer()->notNull(),
                'lockedAt' => $this->dateTime()->notNull(),
            ]);

            // Add indexes
            $this->createIndex(null, OrderLock::tableName(), ['orderId'], true);
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropTable(OrderLock::tableName());
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
