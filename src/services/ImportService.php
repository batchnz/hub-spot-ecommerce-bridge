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
     * Fetches all of the data required for product import from the database
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

        }, $objects);

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
    }
}
