<?php

namespace batchnz\hubspotecommercebridge\models;

use craft\commerce\elements\Order;

class HubspotOrder extends HubspotModel
{
    public ?string $orderStage;
    public ?string $totalPrice;
    public ?string $dealType = 'existingbusiness';
    public ?string $orderNumber;
    public ?string $discountAmount;
    public ?string $discountCode = null;
    public ?string $dateCreated = null;
    public ?string $orderShortNumber = null;

    /**
     * Returns a new model from the passed HubspotCommerceObject
     *
     * @param Order $model
     * @return self
     */
    public static function fromCraftModel($model): self
    {
        //TODO link the properties imported to the properties set in settings
        //TODO fix up the format of this data so they all have the correct data type (e.g. String instead of integer)
        $deal = new self();
        $deal->orderStage = '';
        $deal->totalPrice = $model->getTotalPrice();
        $deal->orderNumber = $model->number;
        $deal->discountAmount = $model->getTotalDiscount();
        $deal->dateCreated = (strtotime($model->dateCreated) * 1000)."";
        $deal->orderShortNumber = $model->getShortNumber();

        return $deal;
    }

    public function rules(): array
    {
        return [
            ...parent::rules(),
            [['orderStage', 'totalPrice', 'dealType', 'orderNumber', 'discountAmount'], 'required'],
        ];
    }
}
