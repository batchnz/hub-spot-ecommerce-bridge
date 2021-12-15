<?php

namespace batchnz\hubspotecommercebridge\models;

use batchnz\hubspotecommercebridge\records\HubspotCommerceObject;
use craft\base\Model;
use yii\base\Exception;

class OrderSettings extends Model
{
    private const ORDER_STAGE = "dealstage";
    private const TOTAL_PRICE = "amount";
    private const DEAL_TYPE = "dealtype";
    private const ORDER_NUMBER = "ip__ecomm_bridge__order_number";
    private const DISCOUNT_AMOUNT = "ip__ecomm_bridge__discount_amount";

    public string $orderStage;
    public string $totalPrice;
    public string $dealType;
    public string $orderNumber;
    public string $discountAmount;
    public ?string $discountCode = null;
    public ?string $dateCreated = null;
    public ?string $orderShortNumber = null;

    public function __construct()
    {
        parent::__construct();
        $this->orderStage = self::ORDER_STAGE;
        $this->totalPrice = self::TOTAL_PRICE;
        $this->dealType = self::DEAL_TYPE;
        $this->orderNumber = self::ORDER_NUMBER;
        $this->discountAmount = self::DISCOUNT_AMOUNT;
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

        $orderSettings = new static();

        $orderSettings->orderStage = $settings->orderStage ?? self::ORDER_STAGE;
        $orderSettings->totalPrice = $settings->totalPrice ?? self::TOTAL_PRICE;
        $orderSettings->dealType = $settings->dealType ?? self::DEAL_TYPE;
        $orderSettings->orderNumber = $settings->orderNumber ?? self::ORDER_NUMBER;
        $orderSettings->discountAmount = $settings->discountAmount ?? self::DISCOUNT_AMOUNT;
        $orderSettings->discountCode = $settings->discountCode ?? null;
        $orderSettings->dateCreated = $settings->dateCreated ?? null;
        $orderSettings->orderShortNumber = $settings->orderShortNumber ?? null;

        return $orderSettings;
    }

    public function rules()
    {
        parent::rules();

        return [
            [['orderStage', 'totalPrice', 'dealType', 'orderNumber'], 'required'],
            ['orderStage', 'compare', 'compareValue' => self::ORDER_STAGE],
            ['totalPrice', 'compare', 'compareValue' => self::TOTAL_PRICE],
            ['dealType', 'compare', 'compareValue' => self::DEAL_TYPE],
            ['orderNumber', 'compare', 'compareValue' => self::ORDER_NUMBER],
            ['discountAmount', 'compare', 'compareValue' => self::DISCOUNT_AMOUNT],

            [['orderStage', 'totalPrice', 'dealType', 'orderNumber', 'discountAmount', 'discountCode', 'dateCreated', 'orderShortNumber'], 'string'],
        ];
    }
}
