<?php

namespace batchnz\hubspotecommercebridge\models;

use batchnz\hubspotecommercebridge\records\HubspotCommerceObject;
use craft\base\Model;
use yii\base\Exception;

class LineItemSettings extends Model
{
    private const PRODUCT_ID = "hs_product_id";
    private const QTY = "quantity";
    private const DESCRIPTION = "description";
    private const PRICE = "price";

    public string $productId;
    public string $qty;
    public string $description;
    public string $price;

    public function __construct()
    {
        parent::__construct();
        $this->productId = self::PRODUCT_ID;
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

        $lineItemSettings->productId = $settings->productId ?? self::PRODUCT_ID;
        $lineItemSettings->qty = $settings->qty ?? self::QTY;
        $lineItemSettings->description = $settings->description ?? self::DESCRIPTION;
        $lineItemSettings->price = $settings->price ?? self::PRICE;

        return $lineItemSettings;
    }

    public function rules(): array
    {
        parent::rules();

        return [
            [['productId', 'qty', 'description', 'price'], 'required'],
            ['productId', 'compare', 'compareValue' => self::PRODUCT_ID],
            ['qty', 'compare', 'compareValue' => self::QTY],
            ['description', 'compare', 'compareValue' => self::DESCRIPTION],
            ['price', 'compare', 'compareValue' => self::PRICE],

            [['productId', 'qty', 'description', 'price'], 'string'],
        ];
    }
}
