<?php

namespace batchnz\hubspotecommercebridge\models;

use batchnz\hubspotecommercebridge\records\HubspotCommerceObject;
use craft\base\Model;
use yii\base\Exception;

class ProductSettings extends Model
{
    private const SKU = "hs_sku";
    private const PRICE = "price";
    private const TITLE = "name";

    public string $sku;
    public string $price;
    public string $title;

    public function __construct()
    {
        parent::__construct();
        $this->sku = self::SKU;
        $this->price = self::PRICE;
        $this->title = self::TITLE;
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

        $productSettings = new static();

        $productSettings->sku = $settings->orderStage ?? self::SKU;
        $productSettings->price = $settings->totalPrice ?? self::PRICE;
        $productSettings->title = $settings->dealType ?? self::TITLE;

        return $productSettings;
    }

    public function rules()
    {
        parent::rules();

        return [
            [['sku', 'price', 'title'], 'required'],
            ['sku', 'compare', 'compareValue' => self::SKU],
            ['price', 'compare', 'compareValue' => self::PRICE],
            ['title', 'compare', 'compareValue' => self::TITLE],

            [['sku', 'title', 'price'], 'string'],
        ];
    }
}
