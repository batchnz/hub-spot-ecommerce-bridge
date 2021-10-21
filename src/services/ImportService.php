<?php

namespace batchnz\hubspotecommercebridge\services;

use batchnz\hubspotecommercebridge\enums\HubSpotActionTypes;
use batchnz\hubspotecommercebridge\enums\HubSpotObjectTypes;
use batchnz\hubspotecommercebridge\jobs\ImportAllJob;
use batchnz\hubspotecommercebridge\Plugin;
use Craft;
use craft\base\Component;
use craft\db\Query;
use craft\db\Table;
use SevenShores\Hubspot\Factory as HubSpotFactory;

/**
 * Class ImportService
 * @package batchnz\hubspotecommercebridge\services
 *
 * Handles all of the logic to do with importing data from Craft Commerce to the HubSpot store
 */
class ImportService extends Component
{
    public const MAX_BATCH_SIZE = 200;

    /**
     * Fetches all of the data required for Product import from the database
     * @return array
     */
    public function fetchProducts(): array
    {
        $query = new Query();
        $query->select('
            [[variants.id]] as variantId,
            [[products.id]] as productId,
            [[variants.sku]] as sku,
            [[variants.price]] as price,
            [[content.title]] as title')
            ->from('{{%commerce_variants}} as variants')
            ->leftJoin('{{%commerce_products}} as products', '[[variants.productId]] = [[products.id]]')
            ->leftJoin(['content' => Table::CONTENT], '[[products.id]] = [[content.elementId]]')
            ->leftJoin(['elements' => Table::ELEMENTS], '[[products.id]] = [[elements.id]]')
            ->where(['elements.dateDeleted' => null])
            ->orderBy(['[[products.id]]' => SORT_DESC]);

        return $query->all();
    }

    /**
     * Prepares the messages object to be sent as a request for Products
     * @param string $action
     * @param array $product
     * @return array
     */
    public function prepareProductMessage(string $action, array $product): array
    {
        $milliseconds = round(microtime(true) * 1000);

        //TODO link the properties imported to the properties set in settings
        return (
            [
                "action" => $action,
                "changedAt" => $milliseconds,
                "externalObjectId" => $product['sku'],
                "properties" => [
                    "price" => $product['price'],
                    "sku" => $product['sku'],
                    "title" => $product['title'],
                ]
            ]
        );
    }

    /**
     * Fetches all of the data required for Customer import from the database
     * @return array
     */
    public function fetchCustomers() :array
    {
        $query = new Query();
        $query->select('
            [[customers.id]] as customerId,
            [[users.email]] as userEmail,
            [[users.firstName]] as userFirstName,
            [[users.lastName]] as userLastName,
            ANY_VALUE([[orders.email]]) as orderEmail,
            [[billingAddress.firstName]] as billingFirstName,
            [[billingAddress.lastName]] as billingLastName,
            [[shippingAddress.firstName]] as shippingFirstName,
            [[shippingAddress.lastName]] as shippingLastName')
            ->from('{{%commerce_customers}} as customers')
            ->leftJoin('{{%commerce_orders}} as orders', '[[customers.id]] = [[orders.customerId]]')
            ->leftJoin('{{%users}} as users', '[[customers.userId]] = [[users.id]]')
            ->leftJoin(['elements' => Table::ELEMENTS], '[[users.id]] = [[elements.id]]')
            ->leftJoin('{{%commerce_addresses}} as billingAddress', '[[customers.primaryBillingAddressId]] = [[billingAddress.id]]')
            ->leftJoin('{{%commerce_addresses}} as shippingAddress', '[[customers.primaryShippingAddressId]] = [[shippingAddress.id]]')
            ->where(['elements.dateDeleted' => null])
            ->groupBy('[[customers.id]]');

        return $query->all();
    }

    /**
     * Prepares the messages object to be sent as a request for Customers
     * Must first filter for some data as it may be stored in multiple places
     * @param string $action
     * @param array $customer
     * @return array
     */
    public function prepareCustomerMessage(string $action, array $customer): array
    {
        $email = $customer['userEmail'] ?? $customer['orderEmail'];
        $firstName = $customer['userFirstName'] ?? $customer['billingFirstName'];
        $firstName = $firstName ?? $customer['shippingFirstName'];
        $lastName = $customer['userLastName'] ?? $customer['billingLastName'];
        $lastName = $lastName ?? $customer['shippingLastName'];

        $milliseconds = round(microtime(true) * 1000);

        //TODO link the properties imported to the properties set in settings
        return ($email || $firstName || $lastName) ? (
        [
            "action" => $action,
            "changedAt" => $milliseconds,
            "externalObjectId" => $customer['customerId'],
            "properties" => [
                "email" => $email ?? "",
                "firstName" => $firstName ?? "",
                "lastName" => $lastName ?? "",
            ]
        ]
        ) : [];
    }

    /**
     * Fetches all of the data required for Order import from the database
     * @return array
     */
    public function fetchOrders() :array
    {
        $query = new Query();
        $query->select('
            [[orders.id]] as orderId,
            [[orders.total]] as total,
            [[orders.dateOrdered]] as dateOrdered,
            [[orders.orderStatusId]] as orderStatusId,
            [[orders.customerId]] as customerId')
            ->from('{{%commerce_orders}} as orders')
            ->leftJoin(['elements' => Table::ELEMENTS], '[[orders.id]] = [[elements.id]]')
            ->where(['elements.dateDeleted' => null]);

        return $query->all();
    }

    /**
     * Prepares the messages object to be sent as a request for Orders
     * @param string $action
     * @param array $order
     * @return array
     */
    public function prepareOrderMessage(string $action, array $order): array
    {
        $milliseconds = round(microtime(true) * 1000);

        //TODO link the properties imported to the properties set in settings
        return (
            [
                "action" => $action,
                "changedAt" => $milliseconds,
                "externalObjectId" => $order['orderId'],
                "properties" => [
                    "totalPrice" => $order['total'],
                    "dateOrdered" => $milliseconds."",
                    "orderStage" => "processed",
                ],
                "associations" => [
                    HubSpotObjectTypes::CONTACT => [$order['customerId'] ?? ""]
                ]
            ]
        );
    }

    /**
     * Fetches all of the data required for LineItem import from the database
     * @return array
     */
    public function fetchLineItems() :array
    {
        $query = new Query();
        $query->select('
            [[lineItems.id]] as lineItemId,
            [[lineItems.price]] as price,
            [[lineItems.qty]] as qty,
            [[lineItems.description]] as description,
            [[orders.id]] as orderId,
            [[variants.sku]] as sku')
            ->from('{{%commerce_lineitems}} as lineItems')
            ->leftJoin('{{%commerce_orders}} as orders', '[[lineItems.orderId]] = [[orders.id]]')
            ->leftJoin('{{%commerce_variants}} as variants', '[[lineItems.purchasableId]] = [[variants.id]]')
            ->leftJoin(['elements' => Table::ELEMENTS], '[[orders.id]] = [[elements.id]]')
            ->where(['elements.dateDeleted' => null]);

        return $query->all();
    }

    /**
     * Prepares the messages object to be sent as a request for LineItems
     * @param string $action
     * @param array $lineItem
     * @return array
     */
    public function prepareLineItemMessage(string $action, array $lineItem): array
    {
        $milliseconds = round(microtime(true) * 1000);

        //TODO link the properties imported to the properties set in settings
        return ($lineItem['orderId'] && $lineItem['sku']) ? (
        [
            "action" => $action,
            "changedAt" => $milliseconds,
            "externalObjectId" => $lineItem['lineItemId'],
            "properties" => [
                "price" => $lineItem['price'],
                "qty" => $lineItem['qty'],
                "description" => $lineItem['description'],
            ],
            "associations" => [
                HubSpotObjectTypes::DEAL => [$lineItem['orderId'] ?? ""],
                HubSpotObjectTypes::PRODUCT => [$lineItem['sku'] ?? ""]
            ]
        ]
        ) : [];
    }

    /**
     * Prepares object data to be in the correct form of the messages which will be sent in the request
     * If there are more objects than the max batch size, then creates batches of messages to be sent
     * Returns an array of batches to be sent
     *
     * @param string $objectType
     * @param string $action
     * @param array $objects
     * @return array
     */
    public function prepareMessages(string $objectType, string $action, array $objects): array
    {
        $messages = array_map(function ($object) use ($objectType, $action) {

            //TODO Abstract this in to use the correct method based on the object type passed in
            if ($objectType === HubSpotObjectTypes::PRODUCT) return $this->prepareProductMessage($action, $object);
            if ($objectType === HubSpotObjectTypes::CONTACT) return $this->prepareCustomerMessage($action, $object);
            if ($objectType === HubSpotObjectTypes::DEAL) return $this->prepareOrderMessage($action, $object);
            if ($objectType === HubSpotObjectTypes::LINE_ITEM) return $this->prepareLineItemMessage($action, $object);

        }, $objects);

        //Removes any empty arrays if there are any
        $messages = array_filter($messages);

        // If the number of objects are less than the max batch size, only one batch needs to be made, return it
        if (count($messages) <= self::MAX_BATCH_SIZE) {
            //Needs to be an array as in this case there is an array of 1 batch
            return [$messages];
        }

        //Batches need to be made with the max batch size
        return array_chunk($messages, self::MAX_BATCH_SIZE, false);
    }


    /**
     * Handle importing of all data. Queries the for all of the necessary datatypes
     * and uses to import the necessary data and associations into HubSpot. The import will be
     * sent to the queue as a job.
     */
    public function importAll()
    {
        $queue = Craft::$app->getQueue();

        $jobId = $queue->push(new ImportAllJob());

        return "Import All Craft Commerce Data to HubSpot has been queued with JobID " . $jobId;
    }
}
