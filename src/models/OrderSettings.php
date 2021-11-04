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

    public string $orderStage;
    public string $totalPrice;
    public string $dealType;
    public string $orderNumber;
    public ?string $dateCreated = null;
    public ?string $orderShortNumber = null;

    public function __construct()
    {
        parent::__construct();
        $this->orderStage = self::ORDER_STAGE;
        $this->totalPrice = self::TOTAL_PRICE;
        $this->dealType = self::DEAL_TYPE;
        $this->orderNumber = self::ORDER_NUMBER;
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

            [['orderStage', 'totalPrice', 'dealType', 'orderNumber', 'dateCreated', 'orderShortNumber'], 'string'],
        ];
    }
}
