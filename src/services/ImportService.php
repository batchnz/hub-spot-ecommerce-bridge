<?php

namespace batchnz\hubspotecommercebridge\services;

use batchnz\hubspotecommercebridge\enums\HubSpotDealStages;
use batchnz\hubspotecommercebridge\enums\HubSpotObjectTypes;
use batchnz\hubspotecommercebridge\jobs\DeleteAllJob;
use batchnz\hubspotecommercebridge\jobs\ImportAllJob;
use Craft;
use craft\base\Component;
use craft\commerce\elements\Variant;
use craft\db\Query;
use craft\db\Table;

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
        $products = Variant::find()
            ->with(['size', 'paintColour', 'paintSheen', 'coatingColour', 'coatingSheen'])
            ->leftJoin('{{%commerce_products}} as products', '[[productId]] = [[products.id]]')->all();

        return array_map(static function ($product) {
            return [
                "price" => $product->price,
                "sku" => $product->sku,
                "title" => $product->product->title,
                "size" => $product->size ? $product->size[0]->title : "",
                "paintColour" => $product->paintColour ? $product->paintColour[0]->title : "",
                "paintSheen" => $product->paintSheen ? $product->paintSheen[0]->title : "",
                "coatingColour" => $product->coatingColour ? $product->coatingColour[0]->title : "",
                "coatingSheen" => $product->coatingSheen ? $product->coatingSheen[0]->title : "",
                "sizeInLitres" => $product->size ? $product->size[0]->sizeInLitres : ""
            ];
        }, $products);
    }

    /**
     * Prepares the messages object to be sent as a request for Products
     * @param string $action
     * @param array $product
     * @return array
     */
    public function prepareProductMessage(array $product, string $action): array
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
                    "size" => $product['size'],
                    "colour" => $product['paintColour'] !== '' ? $product['paintColour'] : $product['coatingColour'],
                    "sheen" => $product["paintSheen"] !== '' ? $product["paintSheen"] : $product['coatingSheen'],
                    "size_litres" => $product['sizeInLitres'],
                ]
            ]
        );
    }

    /**
     * Fetches all of the data required for Customer import from the database
     * @return array
     */
    public function fetchCustomers(): array
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
            [[billingAddress.address1]] as billingAddress,
            [[billingAddress.city]] as billingCity,
            [[billingAddress.phone]] as billingPhone,
            [[billingAddress.businessName]] as billingBusiness,
            [[shippingAddress.firstName]] as shippingFirstName,
            [[shippingAddress.lastName]] as shippingLastName,
            [[shippingAddress.address1]] as shippingAddress,
            [[shippingAddress.city]] as shippingCity,
            [[shippingAddress.phone]] as shippingPhone,
            [[shippingAddress.businessName]] as shippingBusiness,')
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
    public function prepareContactMessage(array $customer, string $action): array
    {
        $email = !empty($customer['userEmail']) ? $customer['userEmail'] : $customer['orderEmail'];

        $firstName = !empty($customer['userFirstName']) ? $customer['userFirstName'] : $customer['billingFirstName'];
        $firstName = !empty($firstName) ? $firstName : $customer['shippingFirstName'];

        $lastName = !empty($customer['userLastName']) ? $customer['userLastName'] : $customer['billingLastName'];
        $lastName = !empty($lastName) ? $lastName : $customer['shippingLastName'];

        $phone = !empty($customer['billingPhone']) ? $customer['billingPhone'] : $customer['shippingPhone'];

        $address = !empty($customer['billingAddress']) ? $customer['billingAddress'] : $customer['shippingAddress'];

        $city = !empty($customer['billingCity']) ? $customer['billingCity'] : $customer['shippingCity'];

        $business = !empty($customer['billingBusiness']) ? $customer['billingBusiness'] : $customer['shippingBusiness'];

        $milliseconds = round(microtime(true) * 1000);

        //TODO link the properties imported to the properties set in settings
        return ($email || $firstName || $lastName) ? (
            [
            "action" => $action,
            "changedAt" => $milliseconds,
            "externalObjectId" => $customer['customerId'],
            "properties" => [
                "email" => !empty($email) ? $email : "",
                "firstName" => !empty($firstName) ? $firstName : $email,
                "lastName" => !empty($lastName) ? $lastName : "",
                "phoneNumber" => !empty($phone) ? $phone : "",
                "address" => !empty($address) ? $address : "",
                "city" => !empty($city) ? $city : "",
                "business" => !empty($business) ? $business : "",
            ]
        ]
        ) : [];
    }

    /**
     * Fetches all of the data required for Order import from the database
     * @return array
     */
    public function fetchOrders(): array
    {
        $query = new Query();
        $query->select('
            [[orders.id]] as orderId,
            [[orders.total]] as total,
            [[orders.dateCreated]] as dateCreated,
            [[orders.reference]] as orderShortNumber,
            [[orders.number]] as orderNumber,
            [[orders.customerId]] as customerId,
            [[orders.totalDiscount]] as discountAmount,
            [[orders.couponCode]] as discountCode,
            [[orderStatuses.handle]] as orderStatus,')
            ->from('{{%commerce_orders}} as orders')
            ->leftJoin('{{%commerce_orderstatuses}} as orderStatuses', '[[orders.orderStatusId]] = [[orderStatuses.id]]')
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
    public function prepareDealMessage(array $order, string $action): array
    {
        //TODO link the properties imported to the properties set in settings
        //TODO fix up the format of this data so they all have the correct data type (e.g. String instead of integer)
        $orderStatus = HubSpotDealStages::ABANDONED;
        if ($order['orderStatus'] && array_key_exists($order['orderStatus'], HubSpotDealStages::PIPELINE)) {
            $orderStatus = HubSpotDealStages::PIPELINE[$order['orderStatus']];
        }

        return (
            [
                "action" => $action,
                "changedAt" => (strtotime($order['dateCreated']) * 1000)."",
                "externalObjectId" => $order['orderId'],
                "properties" => [
                    "totalPrice" => $order['total']."",
                    "dateCreated" => (strtotime($order['dateCreated']) * 1000)."",
                    "orderStage" => $orderStatus,
                    "orderShortNumber" => $order['orderShortNumber']."",
                    "dealType" => "existingbusiness",
                    "orderNumber" => $order['orderNumber']."",
                    "discountAmount" => $order['discountAmount']."",
                    "discountCode" => $order['discountCode']."",
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
    public function fetchLineItems(): array
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
    public function prepareLineItemMessage(array $lineItem, string $action): array
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
                'hs_assoc__deal_id' => $lineItem['orderId'] ?? "",
                'hs_assoc__product_id' => $lineItem['sku'] ?? "",
            ],
            "associations" => [
                HubSpotObjectTypes::DEAL => [$lineItem['orderId'] ?? ""],
                HubSpotObjectTypes::PRODUCT => [$lineItem['sku'] ?? ""]
            ]
        ]
        ) : [];
    }

    /**
     * Handle importing of all data. Queries the for all of the necessary datatypes
     * and uses to import the necessary data and associations into HubSpot. The import will be
     * sent to the queue as a job.
     */
    public function importAll(): string
    {
        $queue = Craft::$app->getQueue();

        $jobId = $queue->push(new ImportAllJob());

        return "Import All Craft Commerce Data to HubSpot has been queued with JobID " . $jobId;
    }

    /**
     * Handle importing of all data. Queries the for all of the necessary datatypes
     * and uses to import the necessary data and associations into HubSpot. The import will be
     * sent to the queue as a job.
     */
    public function deleteAll(): string
    {
        $queue = Craft::$app->getQueue();

        $jobId = $queue->push(new DeleteAllJob());

        return "Delete All Craft Commerce Data from HubSpot has been queued with JobID " . $jobId;
    }

    public function normalizeObjectType($objectType): string
    {
        $value = ucwords(str_replace(['-', '_'], ' ', $objectType));

        return "prepare" . str_replace(' ', '', $value) . "Message";
    }
}
