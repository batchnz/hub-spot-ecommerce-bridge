<?php

namespace batchnz\hubspotecommercebridge\models;

use craft\elements\User;

class HubspotCustomer extends HubspotModel
{
    public ?string $firstName;
    public ?string $lastName;
    public ?string $email;
    public ?string $phoneNumber = null;
    public ?string $address = null;
    public ?string $city = null;
    public ?string $business = null;

    /**
     * Returns a new model from the passed HubspotCommerceObject
     *
     * @param User $model
     * @return self
     */
    public static function fromCraftModel($model): self
    {
        $contact = new self();
        $contact->firstName = (string)$model->firstName;
        $contact->lastName = (string)$model->lastName;
        $contact->email = (string)$model->email;
        $contact->phoneNumber = (string)$model->phoneNumberPrimary;
        $contact->address = (string)$model->address;
        $contact->city = (string)($model->getAddresses()[0]->locality ?? null);

        return $contact;
    }

    public function rules(): array
    {
        return [
            ...parent::rules(),
            [['firstName', 'lastName', 'email'], 'required'],
        ];
    }
}