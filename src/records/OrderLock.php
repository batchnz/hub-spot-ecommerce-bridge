<?php

namespace batchnz\hubspotecommercebridge\records;

use Craft;
use craft\db\ActiveRecord;
use DateTime;
use Exception;

class OrderLock extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return '{{%hubspot_commerce_order_lock}}';
    }

    /**
     * @throws Exception
     */
    public static function lockOrder($order): void
    {
        $lockRecord = [
            'orderId' => $order->id,
            'lockedAt' => (new DateTime())->format('Y-m-d H:i:s'),
        ];

        Craft::$app->db->createCommand()
            ->insert(self::tableName(), $lockRecord)
            ->execute();
    }

    /**
     * @throws Exception
     */
    public static function unlockOrder($order): void
    {
        Craft::$app->db->createCommand()
            ->delete(self::tableName(), ['orderId' => $order->id])
            ->execute();
    }
}
