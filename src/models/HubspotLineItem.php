<?php

namespace batchnz\hubspotecommercebridge\models;

use craft\commerce\records\LineItem;

class HubspotLineItem extends HubspotModel
{
    public ?string $productId;
    public ?string $qty;
    public ?string $description;
    public ?string $price;

    /**
     * Returns a new model from the passed HubspotCommerceObject
     *
     * @param LineItem $model
     * @return self
     */
    public static function fromCraftModel($model): self
    {
        $lineItem = new self();
        $lineItem->qty = (string)$model->qty;
        $lineItem->description = $model->description;
        $lineItem->price = (string)$model->price;

        return $lineItem;
    }

    public function rules(): array
    {
        return [
            ...parent::rules(),
            [['productId', 'qty', 'description', 'price'], 'required'],
        ];
    }
}
