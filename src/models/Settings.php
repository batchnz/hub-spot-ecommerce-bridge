<?php

namespace batchnz\hubspotecommercebridge\models;

use craft\base\Model;

class Settings extends Model
{
    public $apiKey = "";
    public $webhookUri = "";

    public function rules()
    {
        return [
            [['apiKey'], 'required'],
            [['apiKey'], 'string'],
        ];
    }
}
