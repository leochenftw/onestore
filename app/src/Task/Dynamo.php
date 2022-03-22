<?php

namespace App\Web\Task;

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Marshaler;
use Aws\Result;
use Leochenftw\eCommerce\eCollector\Model\Order;
use Leochenftw\eCommerce\eCollector\Model\Product;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\BuildTask;
use SilverStripe\Dev\Debug;
use SilverStripe\ORM\DB;

class Dynamo extends BuildTask
{
    private const BATCH_SIZE = 100;

    protected $enabled = true;
    protected $title = 'Emit test';
    protected $description = 'Test socket.io emitter';

    private static $segment = 'dynamo';

    private ?DynamoDbClient $client = null;
    private ?Marshaler $marshaler = null;
    private array $ignoreIDList = [];


    private function migrateOrders()
    {
        $list = Order::get()
            ->exclude(['Migrated' => true])
            ->sort(['ID' => 'ASC'])
            ->limit(static::BATCH_SIZE)
        ;

        if ($list->exists()) {
            foreach ($list as $order) {
                // $key = [
                //     'ID' => $order->MerchantReference,
                //     'CustomerID' => $order->CustomerID,
                // ];
                // $exists = $this->client->getItem([
                //     'TableName' => 'MerchantCloud_Orders',
                //     'Key' => $this->marshaler->marshalItem($key),
                // ]);

                // if (empty($exists->get('Item'))) {
                $item = $this->marshaler->marshalItem($order->jsonSerialize());

                $this->client->putItem([
                        'TableName' => 'MerchantCloud_Orders',
                        'Item' => $item,
                    ]);

                DB::alteration_message("Order#{$order->ID} has been migrated");
                // } else {
                //     DB::alteration_message("Order#{$order->ID} already exists. Ignore", 'obsolete');
                // }

                $order->Migrated = true;
                $order->write();
            }

            return $this->migrateOrders();
        }

        DB::alteration_message('All record migrated');
    }

    private function migrateProducts()
    {
        $list = Product::get();

        Debug::dump($list->count());
    }


    public function run($request)
    {
        $this->client = new DynamoDbClient($this->SDKConfig);
        $this->marshaler = new Marshaler();

        // $results = $this->client->scan([
        //     'TableName' => 'MerchantCloud_Orders'
        // ]);

        // $n = 0;

        // while (!empty($results->get('LastEvaluatedKey'))) {
        //     $items = $results->get('Items');
        //     $n += count($items);
        //     $IDs = array_map(fn ($item) => $item['ID']['N'], $items);
        //     $this->ignoreIDList = array_merge($this->ignoreIDList, $IDs);
        //     Debug::dump($this->ignoreIDList);
        //     die;
        //     $results = $this->client->scan([
        //         'TableName' => 'MerchantCloud_Orders',
        //         'ExclusiveStartKey' => $results->get('LastEvaluatedKey'),
        //     ]);
        // }

        // $this->migrateProducts();
        $this->migrateOrders();

        // $item = $marshaler->marshalJson('
        //     {
        //         "ID": 2022,
        //         "title": "wtf",
        //         "info": {
        //             "plot": "Nothing happens at all.",
        //             "rating": 0
        //         }
        //     }
        // ');

        // Debug::dump($item);
        // die;

        // $results = $this->client->putItem([
        //     'TableName' => 'Movies',
        // ]);

        // scan([
        //     'TableName' => 'MerchantCloud_Orders',
        // ]);

        // if ($results instanceof Result) {
        //     Debug::dump($results);
        // }
    }
}
