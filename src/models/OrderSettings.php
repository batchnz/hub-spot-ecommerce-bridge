<?php

namespace batchnz\hubspotecommercebridge\models;

use craft\base\Model;

class OrderSettings extends Model
{
    public $orderStage;
    public $totalPrice;

    public function __construct()
    {
        parent::__construct();
        $this->orderStage = "dealstage";
        $this->totalPrice = "amount";
    }

    public function rules()
    {
        parent::rules();

        return [
            [['amount', 'dealstage'], 'required'],
            [['dealstage'], 'string'],
            [['amount'], 'integer'],
        ];
    }
}
