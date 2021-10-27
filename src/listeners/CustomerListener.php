<?php


/**
 * HubSpot Ecommerce Bridge plugin for Craft CMS 3.x
 *
 * Uses the HubSpot Ecommerce Bridge to sync data from Craft Commerce
 *
 * @link      https://www.batch.nz/
 * @copyright Copyright (c) 2021 Daniel Siemers
 */

namespace batchnz\hubspotecommercebridge\listeners;

use batchnz\hubspotecommercebridge\enums\HubSpotActionTypes;
use batchnz\hubspotecommercebridge\enums\HubSpotObjectTypes;
use batchnz\hubspotecommercebridge\jobs\ActionOneJob;
use batchnz\hubspotecommercebridge\Plugin;
use Craft;
use craft\commerce\events\CustomerEvent;
use craft\commerce\events\LineItemEvent;
use craft\events\ModelEvent;
use SevenShores\Hubspot\Factory as HubSpotFactory;
use yii\base\Event;

class CustomerListener
{
    public static function upsert(CustomerEvent $event)
    {
        $customer = self::modelCustomer($event->customer);

        $queue = Craft::$app->getQueue();

        $queue->push(new ActionOneJob([
            "description" => Craft::t('hub-spot-ecommerce-bridge', 'Upsert Craft Commerce Customer Data to HubSpot'),
            "objectType" => HubSpotObjectTypes::CONTACT,
            "action" => HubSpotActionTypes::UPSERT,
            "object" => $customer,
        ]));
    }

//    public static function delete(LineItemEvent $event): void
//    {
//        $lineItem = self::modelCustomer($event->lineItem);
//        $queue = Craft::$app->getQueue();
//
//        $queue->push(new ActionOneJob([
//            "description" => Craft::t('hub-spot-ecommerce-bridge', 'Delete Craft Commerce Line Item Data from HubSpot'),
//            "objectType" => HubSpotObjectTypes::LINE_ITEM,
//            "action" => HubSpotActionTypes::DELETE,
//            "object" => $lineItem,
//        ]));
//    }

    protected static function modelCustomer($customer): array
    {
        $user = $customer->getUser();
        $billingAddress = $customer->getPrimaryBillingAddress();
        $shippingAddress = $customer->getPrimaryShippingAddress();
        $orders = $customer->getOrders();
        $activeCarts = $customer->getActiveCarts();
        $inactiveCarts = $customer->getInactiveCarts();
        $orderEmail = "";

        foreach(array_merge($orders, $activeCarts, $inactiveCarts) as $order) {
            if ($order->email) {
                $orderEmail = $order->email;
                break;
            }
        }

        //TODO make this dynamic dependant on the settings set by the user
        return ([
            "customerId" => $customer->id,
            "userEmail" => $customer->getEmail(),
            "orderEmail" => $orderEmail,
            "userFirstName" => $user ? $user->firstName : "",
            "billingFirstName" => $billingAddress ? $billingAddress->firstName : "",
            "shippingFirstName" => $shippingAddress ? $shippingAddress->firstName : "",
            "userLastName" => $user ? $user->lastName : "",
            "billingLastName" => $billingAddress ? $billingAddress->lastName : "",
            "shippingLastName" => $shippingAddress ? $shippingAddress->lastName : "",
            "billingPhone" => $billingAddress ? $billingAddress->phone : "",
            "shippingPhone" => $shippingAddress ? $shippingAddress->phone : "",
            "billingAddress" => $billingAddress ? $billingAddress->address1 : "",
            "shippingAddress" => $shippingAddress ? $shippingAddress->address1 : "",
            "billingCity" => $billingAddress ? $billingAddress->city : "",
            "shippingCity" => $shippingAddress ? $shippingAddress->city : "",
            "billingBusiness" => $billingAddress ? $billingAddress->businessName : "",
            "shippingBusiness" => $shippingAddress ? $shippingAddress->businessName : "",
        ]);
    }
}
