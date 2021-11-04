<?php

namespace batchnz\hubspotecommercebridge\models;

use batchnz\hubspotecommercebridge\records\HubspotCommerceObject;
use craft\base\Model;
use yii\base\Exception;

class LineItemSettings extends Model
{
    private const QTY = "quantity";
    private const DESCRIPTION = "description";
    private const PRICE = "price";

    public string $qty;
    public string $description;
    public string $price;

    public function __construct()
    {
        parent::__construct();
        $this->qty = self::QTY;
        $this->description = self::DESCRIPTION;
        $this->price = self::PRICE;
    }

    /**
     * Returns a new model from the passed HubspotCommerceObject
     *
     * @param HubspotCommerceObject $object A Hubspot Commerce object
     *
     * @return self
     * @throws Exception
     * @throws \JsonException
     */
    public static function fromHubspotObject(HubspotCommerceObject $object): self
    {
        $settings = json_decode($object->settings, false, 512, JSON_THROW_ON_ERROR);

        $lineItemSettings = new static();

        $lineItemSettings->qty = $settings->orderStage ?? self::QTY;
        $lineItemSettings->description = $settings->totalPrice ?? self::DESCRIPTION;
        $lineItemSettings->price = $settings->dealType ?? self::PRICE;

        return $lineItemSettings;
    }

    public function rules()
    {
        parent::rules();

        return [
            [['qty', 'description', 'price'], 'required'],
            ['qty', 'compare', 'compareValue' => self::QTY],
            ['description', 'compare', 'compareValue' => self::DESCRIPTION],
            ['price', 'compare', 'compareValue' => self::PRICE],

            [['qty', 'description', 'price'], 'string'],
        ];
    }
}
