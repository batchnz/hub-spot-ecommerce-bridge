<?php

namespace batchnz\hubspotecommercebridge\records;

use craft\db\ActiveRecord;

/**
 * Active Record for saving settings for Hubspot Commerce Objects
 *
 * @author Daniel Siemers <daniel@batch.nz>
 */
class HubspotCommerceObject extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return '{{%hubspot_commerce_objects}}';
    }
}
