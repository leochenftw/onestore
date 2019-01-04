<?php

namespace App\Web\Task;
use SilverStripe\Dev\BuildTask;
use Leochenftw\Debugger;
use GuzzleHttp\Client;
use App\Web\Layout\ProductPage;
use App\Web\Layout\ProductLandingPage;
use SilverStripe\Versioned\Versioned;
use App\Web\Model\Supplier;

/**
 * Description
 *
 * @package silverstripe
 * @subpackage mysite
 */
class ProductMigrator extends BuildTask
{
    private $landing_page   =   null;
    /**
     * @var bool $enabled If set to FALSE, keep it from showing in the list
     * and from being executable through URL or CLI.
     */
    protected $enabled = true;

    /**
     * @var string $title Shown in the overview on the TaskRunner
     * HTML or CLI interface. Should be short and concise, no HTML allowed.
     */
    protected $title = 'Product Migrator';

    /**
     * @var string $description Describe the implications the task has,
     * and the changes it makes. Accepts HTML formatting.
     */
    protected $description = 'Migrate product from CSV';

    /**
     * This method called via the TaskRunner
     *
     * @param SS_HTTPRequest $request
     */
    public function run($request)
    {
        if ($request->getHeader('User-Agent') != 'CLI') {
            print '<span style="color: red; font-weight: bold; font-size: 24px;">This task is for CLI use only</span><br />';
            print '<em style="font-size: 14px;"><strong>Usage</strong>: sake dev/tasks/' . str_replace('\\', '-', __CLASS__) . '</em>';
            return false;
        }

        if ($this->landing_page = ProductLandingPage::get()->first()) {
            $products   =   $this->fetch_products();
            $this->import($products);
            return true;
        }

        $this->terminate('Please create a product landing page first!');
    }

    private function import(&$products)
    {
        $i  =   0;
        $n  =   0;
        foreach ($products as $product) {
            if (!empty($product->manufacturer)) {
                if ($product->manufacturer == '1') {
                    $n++;
                    Debugger::inspect("\033[01;31mSkipping " . $product->title . "\033[0m.", false);
                    continue;
                } else {
                    $supplier           =   Supplier::get()->filter(['Title' => $product->manufacturer])->first();
                    if (empty($supplier)) {
                        $supplier   =   Supplier::create();
                    }
                    $supplier->Title    =   $product->manufacturer;
                    $supplier->write();
                }
            }

            $item   =   ProductPage::get()->filter(['Barcode' => $product->barcode])->first();

            if (empty($item)) {
                $item           =   ProductPage::create();
                $item->Barcode  =   $product->barcode;
                $item->ParentID =   $this->landing_page->ID;
            }

            $item->Title            =   $product->title;
            $item->Alias            =   $product->chinese_title;
            $item->MeasurementUnit  =   $product->measurement;
            $item->StockCount       =   $product->stock_count;
            $item->Cost             =   $product->cost;
            $item->Price            =   $product->price;
            $item->UnitWeight       =   $product->weight;

            $item->write();
            $item->writeToStage('Live');

            if (!empty($supplier)) {
                $item->Supplier()->add($supplier->ID);
                $item->write();
                $item->writeToStage('Live');
            }

            $i++;
            Debugger::inspect("\033[01;31m" . $item->Title . "\033[0m" . ' has been created/updated.', false);
        }

        Debugger::inspect("\033[01;31m" . $i . " items \033[0m added/created.\n\033[01;31m" . $n . " items \033[0m skipped.", false);
    }

    private function fetch_products()
    {
        $client = new Client([
            'base_uri' => 'https://merchantcloud.leochen.co.nz/'
        ]);

        $response = $client->request(
            'GET',
            'products',
            array(
                'query' =>  [
                                'supplier'  =>  2,
                                'page'      =>  -1,
                                'detailed'  =>  true
                            ]
            )
        );

        $raw = json_decode($response->getBody());
        return $raw->data;
    }

    private function terminate($message)
    {
        echo "\033[01;31m\n";
        print PHP_EOL;
        print $message;
        print PHP_EOL;
        print PHP_EOL;
        echo "\033[0m";
        die;
    }
}
