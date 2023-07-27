<?php

namespace batchnz\hubspotecommercebridge\models;

use craft\base\Model;

class Settings extends Model
{
    public string $apiKey = "";

    public function rules(): array
    {
        return [
            [['apiKey'], 'required'],
            [['apiKey'], 'string'],
        ];
    }
}
