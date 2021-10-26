<?php

namespace batchnz\hubspotecommercebridge\models;

use craft\base\Model;

class LineItemSettings extends Model
{
    public $qty;
    public $description;
    public $price;

    public function __construct()
    {
        parent::__construct();
        $this->qty = "quantity";
        $this->description = "description";
        $this->price = "price";
    }

    public function rules()
    {
        parent::rules();

        return [
            [['qty', 'description', 'price'], 'required'],
            [['description'], 'string'],
            [['price', 'qty'], 'integer'],
        ];
    }
}
