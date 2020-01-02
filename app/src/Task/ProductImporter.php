<?php

namespace App\Web\Task;
use SilverStripe\Dev\BuildTask;
use Leochenftw\Debugger;
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
class ProductImporter extends BuildTask
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
    protected $title = 'Import Products';

    /**
     * @var string $description Describe the implications the task has,
     * and the changes it makes. Accepts HTML formatting.
     */
    protected $description = 'Import products from JSON';

    /**
     * This method called via the TaskRunner
     *
     * @param SS_HTTPRequest $request
     */
    public function run($request)
    {
        if ($this->landing_page = ProductLandingPage::get()->first()) {
            return $this->do_import($request);
        }

        $this->terminate('Please create a product landing page first!');
    }

    private function do_import(&$request)
    {
        if ($request->getHeader('User-Agent') != 'CLI') {
            print '<span style="color: red; font-weight: bold; font-size: 24px;">This task is for CLI use only</span><br />';
            print '<em style="font-size: 14px;"><strong>Usage</strong>: sake dev/tasks/' . str_replace('\\', '-', get_class($this)) . ' {path_to_file}';
            return;
        }

        if (!empty($request->getVar('args'))) {
            $args   =   $request->getVar('args');

            if (count($args) == 0) {
                print 'Missing arguments';
                print PHP_EOL;
                print 'Usage: sake dev/tasks/' . str_replace('\\', '-', get_class($this)) . ' {path_to_file}';
                print PHP_EOL;
                print PHP_EOL;

                return false;
            }

            $json_file  =   $args[0];

            if (file_exists($json_file)) {
                $str        =   file_get_contents($json_file);
                $products   =   json_decode($str);

                $duplicates =   $this->duplicate_barcodes($products);

                if (!empty($duplicates) && empty($args[1])) {
                    Debugger::inspect($duplicates, false);
                    return $this->terminate('You have duplicated barcodes in your data');
                }

                $i  =   0;
                $n  =   0;

                foreach ($products as $product) {
                    if (strpos($product->Barcode, '+') !== false) {
                        $n++;
                        print $product->Barcode;
                        print PHP_EOL;
                    } else {
                        if (!empty($product->Supplier)) {
                            $supplier   =   Supplier::get()->filter(['Title' => $product->Supplier])->first();
                            if (empty($supplier)) {
                                $supplier   =   Supplier::create();
                            }
                            $supplier->Title    =   $product->Supplier;
                            $supplier->write();
                        }

                        $item   =   ProductPage::get()->filter(['Barcode' => $product->Barcode])->first();

                        if (empty($item)) {
                            $item           =   ProductPage::create();
                            $item->Barcode  =   $product->Barcode;
                            $item->ParentID =   $this->landing_page->ID;
                        }

                        $item->Title            =   $product->English;
                        $item->Alias            =   $product->Chinese;
                        $item->StockCount       =   empty($product->Stock) ? 0 : $product->Stock;
                        $item->Cost             =   $product->Cost;
                        $item->Price            =   $product->Price;

                        $item->write();
                        $item->writeToStage('Live');

                        if (!empty($supplier)) {
                            $item->Supplier()->add($supplier->ID);
                            $item->write();
                            $item->writeToStage('Live');
                        }

                        $i++;
                        print "\033[01;31m" . $item->Title . "\033[0m" . ' has been created/updated.';
                        print PHP_EOL;
                    }
                }

                Debugger::inspect("\033[01;31m" . $i . " items \033[0m added/created.\n\033[01;31m" . $n . " items \033[0m skipped.", false);
            }
        }

        print PHP_EOL;
    }

    private function duplicate_barcodes(&$products)
    {
        $barcodes       =   [];
        foreach ($products as $product)
        {
            if (strpos($product->Barcode, '+') !== false) {
                $n++;
                return $this->terminate('Barcode contains "+" sign: ' . $product->Barcode);
            }

            if (empty($barcodes[$product->Barcode])) {
                $barcodes[$product->Barcode]    =   1;
            } else {
                $barcodes[$product->Barcode]++;
            }
        }

        foreach ($barcodes as $key => $value) {
            if ($value <= 1) {
                unset($barcodes[$key]);
            }
        }

        return $barcodes;
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
