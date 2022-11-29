<?php

namespace batchnz\hubspotecommercebridge\models;

use craft\commerce\records\LineItem;

class HubspotLineItem extends HubspotModel
{
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
        $lineItem->qty = $model->qty;
        $lineItem->description = $model->getDescription();
        $lineItem->price = $model->getPrice();

        return $lineItem;
    }

    public function rules(): array
    {
        return [
            ...parent::rules(),
            [['qty', 'description', 'price'], 'required'],
        ];
    }
}
