<?php

namespace batchnz\hubspotecommercebridge\models;

use craft\base\Model;

class Settings extends Model
{
    public $apiKey = "";
    public $storeId = "";
    public $storeLabel = "";
    public $storeAdminUri = "";
    public $webhookUri = "";

    public function rules(): array
    {
        return [
            [['apiKey', 'storeId', 'storeLabel', 'storeAdminUri'], 'required'],
            [['apiKey', 'storeId', 'storeLabel', 'storeAdminUri'], 'string'],
        ];
    }
}
