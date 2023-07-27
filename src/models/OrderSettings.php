<?php

namespace batchnz\hubspotecommercebridge\models;

use batchnz\hubspotecommercebridge\records\HubspotCommerceObject;
use craft\base\Model;
use yii\base\Exception;

class OrderSettings extends Model
{
    private const ORDER_NUMBER = "craft_order_number";
    private const ORDER_NAME = "dealname";
    private const ORDER_STAGE = "dealstage";
    private const TOTAL_PRICE = "amount";
    private const DEAL_TYPE = "dealtype";

    public string $orderNumber;
    public string $orderName;
    public string $orderStage;
    public string $totalPrice;
    public string $dealType;
    public ?string $discountAmount = null;
    public ?string $discountCode = null;
    public ?string $createDate = null;
    public ?string $orderShortNumber = null;

    public ?array $orderStages = [];

    public function __construct()
    {
        parent::__construct();
        $this->orderNumber = self::ORDER_NUMBER;
        $this->orderName = self::ORDER_NAME;
        $this->orderStage = self::ORDER_STAGE;
        $this->totalPrice = self::TOTAL_PRICE;
        $this->dealType = self::DEAL_TYPE;
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

        $orderSettings->orderNumber = $settings->orderNumber ?? self::ORDER_NUMBER;
        $orderSettings->orderName = $settings->orderName ?? self::ORDER_NAME;
        $orderSettings->orderStage = $settings->orderStage ?? self::ORDER_STAGE;
        $orderSettings->totalPrice = $settings->totalPrice ?? self::TOTAL_PRICE;
        $orderSettings->dealType = $settings->dealType ?? self::DEAL_TYPE;
        $orderSettings->discountAmount = $settings->discountAmount ?? null;
        $orderSettings->discountCode = $settings->discountCode ?? null;
        $orderSettings->createDate = $settings->createDate ?? null;
        $orderSettings->orderShortNumber = $settings->orderShortNumber ?? null;
        $orderSettings->orderStages = (array)($settings->orderStages ?? []);

        return $orderSettings;
    }

    public function rules(): array
    {
        parent::rules();

        return [
            [['orderName', 'orderStage', 'totalPrice', 'dealType', 'orderNumber'], 'required'],
            ['orderNumber', 'compare', 'compareValue' => self::ORDER_NUMBER],
            ['orderName', 'compare', 'compareValue' => self::ORDER_NAME],
            ['orderStage', 'compare', 'compareValue' => self::ORDER_STAGE],
            ['totalPrice', 'compare', 'compareValue' => self::TOTAL_PRICE],
            ['dealType', 'compare', 'compareValue' => self::DEAL_TYPE],

            [$this->getArrayKeys(), 'string'],
        ];
    }

    /**
     * Returns all array keys that have a property in Hubspot
     * @return array
     */
    public function getArrayKeys(): array
    {
        return array_keys($this->getAttributes(null, ['orderStages']));
    }

    /**
     * Returns the unique key used to identify the object in Hubspot
     *
     * @return string
     */
    public function uniqueKey(): string
    {
        return 'orderNumber';
    }
}
