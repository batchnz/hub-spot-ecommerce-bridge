<?php

namespace batchnz\hubspotecommercebridge\models;

use craft\base\Model;

class HubspotModel extends Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public static function fromCraftModel($model): self
    {
        return new self();
    }

    public function rules(): array
    {
        return [
            ...parent::rules(),
            [array_keys($this->attributes), 'string'],
        ];
    }
}
