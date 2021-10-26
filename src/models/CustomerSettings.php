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
    public ?string $phoneNumber;

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
     */
    public static function fromHubspotObject(HubspotCommerceObject $object): self
    {
        $settings = json_decode($object->settings);

        if (json_last_error() === JSON_THROW_ON_ERROR) {
            throw new Exception('Could not decode Customer settings.');
        }

        $customerSettings = new static();

        $customerSettings->firstName = $settings->firstName ?? "";
        $customerSettings->lastName = $settings->lastName ?? "";
        $customerSettings->email = $settings->email ?? "";
        $customerSettings->phoneNumber = $settings->phoneNumber ?? null;

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

            [['firstName', 'lastName', 'email', 'phoneNumber'], 'string'],
        ];
    }
}
