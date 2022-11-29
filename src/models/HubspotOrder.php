<?php

namespace batchnz\hubspotecommercebridge\models;

use batchnz\hubspotecommercebridge\enums\HubSpotObjectTypes;
use batchnz\hubspotecommercebridge\Plugin;
use batchnz\hubspotecommercebridge\records\HubspotCommerceObject;
use Craft;
use craft\commerce\elements\Order;

class HubspotOrder extends HubspotModel
{
    public ?string $orderName;
    public ?string $orderStage;
    public ?string $totalPrice;
    public ?string $dealType = 'existingbusiness';
    public ?string $orderNumber;
    public ?string $discountAmount;
    public ?string $discountCode = null;
    public ?string $createDate = null;
    public ?string $orderShortNumber = null;

    /**
     * Returns a new model from the passed HubspotCommerceObject
     *
     * @param Order $model
     * @return self
     */
    public static function fromCraftModel($model): self
    {
        $deal = new self();

        $dealSchema = HubspotCommerceObject::findOne(['objectType' => HubSpotObjectTypes::DEAL]);
        if (!$dealSchema) {
            Craft::error('The DEAL schema is missing from the database.', Plugin::HANDLE);
        }

        try {
            $orderSettings = OrderSettings::fromHubspotObject($dealSchema);
            $orderSettings->validate();
            $deal->orderStage = $orderSettings->orderStages[$model->getOrderStatus()->handle] ?? null;
        } catch (\Exception|\JsonException $e) {
            Craft::error($e->getMessage(), Plugin::HANDLE);
        }

        $deal->orderName = $model->getShortNumber();
        $deal->totalPrice = (string)$model->getTotalPrice();
        $deal->orderNumber = (string)$model->number;
        $deal->discountAmount = (string)$model->getTotalDiscount();
        $deal->createDate = (string)(strtotime($model->dateCreated->getTimestamp()) * 1000);
        $deal->orderShortNumber = $model->getShortNumber();

        return $deal;
    }

    public function rules(): array
    {
        return [
            ...parent::rules(),
            [['orderNumber', 'orderName', 'totalPrice', 'dealType', 'orderNumber', 'discountAmount'], 'required'],
        ];
    }
}
