<?php

namespace batchnz\hubspotecommercebridge\models;

use craft\base\Model;

class ProductSettings extends Model
{
    public $sku;
    public $price;
    public $title;

    public function __construct()
    {
        parent::__construct();
        $this->sku = "hs_sku";
        $this->price = "price";
        $this->title = "name";
    }

    public function rules()
    {
        parent::rules();

        return [
            [['sku', 'price', 'title'], 'required'],
            [['sku', 'title'], 'string'],
            [['price'], 'integer'],
        ];
    }
}
