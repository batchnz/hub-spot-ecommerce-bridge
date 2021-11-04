<?php

namespace batchnz\hubspotecommercebridge\models;

use batchnz\hubspotecommercebridge\records\HubspotCommerceObject;
use craft\base\Model;
use yii\base\Exception;

class CustomerSettings extends Model
{
    private const FIRST_NAME = "firstname";
    private const LAST_NAME = "lastname";
    private const EMAIL = "email";

    public string $firstName;
    public string $lastName;
    public string $email;
    public ?string $phoneNumber = null;
    public ?string $address = null;
    public ?string $city = null;
    public ?string $business = null;

    public function __construct()
    {
        parent::__construct();
        $this->firstName = self::FIRST_NAME;
        $this->lastName = self::LAST_NAME;
        $this->email = self::EMAIL;
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

        $customerSettings = new static();

        $customerSettings->firstName = $settings->firstName ?? self::FIRST_NAME;
        $customerSettings->lastName = $settings->lastName ?? self::LAST_NAME;
        $customerSettings->email = $settings->email ?? self::EMAIL;
        $customerSettings->phoneNumber = $settings->phoneNumber ?? null;
        $customerSettings->address = $settings->address ?? null;
        $customerSettings->city = $settings->city ?? null;
        $customerSettings->business = $settings->business ?? null;

        return $customerSettings;
    }

    public function rules()
    {
        parent::rules();

        return [

            [['firstName', 'lastName', 'email'], 'required'],
            ['firstName', 'compare', 'compareValue' => self::FIRST_NAME],
            ['lastName', 'compare', 'compareValue' => self::LAST_NAME],
            ['email', 'compare', 'compareValue' => self::EMAIL],

            [['firstName', 'lastName', 'email', 'phoneNumber', 'address', 'city', 'business'], 'string'],
        ];
    }
}
