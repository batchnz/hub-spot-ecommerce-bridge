<?php

namespace batchnz\hubspotecommercebridge\models;

use craft\commerce\elements\Variant;

class HubspotProduct extends HubspotModel
{
    public string $sku;
    public ?string $price;
    public ?string $title;
    public ?string $size = null;
    public ?string $colour = null;
    public ?string $sheen = null;
    public ?string $size_litres = null;

    /**
     * Returns a new model from the passed HubspotCommerceObject
     *
     * @param Variant $model
     * @return self
     */
    public static function fromCraftModel($model): self
    {
        $product = new self();
        $product->sku = $model->sku;
        $product->price = (string)$model->price;
        $product->title = (string)$model->title;
        $product->size = (string)$model->size?->one()?->title;
        $product->colour = (string)($model->paintColour?->one()?->title ?? $model->coatingColour?->one()?->title);
        $product->sheen = (string)($model->paintSheen?->one()?->title ?? $model->coatingSheen?->one()?->title);
        $product->size_litres = (string)$model->size?->one()?->sizeInLitres;

        return $product;
    }

    public function rules(): array
    {
        return [
            ...parent::rules(),
            [['sku', 'price', 'title'], 'required'],
        ];
    }
}
