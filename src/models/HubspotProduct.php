<?php

namespace batchnz\hubspotecommercebridge\models;

use batchnz\hubspotecommercebridge\records\HubspotCommerceObject;
use craft\base\Model;
use craft\commerce\elements\Variant;

class HubspotProduct extends Model
{
    public string $sku;
    public ?string $price;
    public ?string $title;
    public ?string $size = null;
    public ?string $colour = null;
    public ?string $sheen = null;
    public ?string $size_litres = null;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Returns a new model from the passed HubspotCommerceObject
     *
     * @param HubspotCommerceObject $object A Hubspot Commerce object
     *
     * @return self
     */
    public static function fromCraftProduct(Variant $variant): self
    {
        $product = new self();
        $product->sku = $variant->sku;
        $product->price = (string)$variant->price;
        $product->title = (string)$variant->title;
        $product->size = (string)$variant->size?->one()?->title;
        $product->colour = (string)($variant->paintColour?->one()?->title ?? $variant->coatingColour?->one()?->title);
        $product->sheen = (string)($variant->paintSheen?->one()?->title ?? $variant->coatingSheen?->one()?->title);
        $product->size_litres = (string)$variant->size?->one()?->sizeInLitres;

        return $product;
    }

    public function rules(): array
    {
        parent::rules();

        return [
            [['sku', 'price', 'title'], 'required'],
            [array_keys($this->attributes), 'string'],
        ];
    }
}
