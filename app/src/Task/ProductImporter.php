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
        return $this->do_import($request);
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

                $i  =   0;
                $n  =   0;
                foreach ($products as $product) {
                    Debugger::inspect($product);
                    // if (!empty($product->manufacturer)) {
                    //     if ($product->manufacturer == '1') {
                    //         $n++;
                    //         Debugger::inspect("\033[01;31mSkipping " . $product->title . "\033[0m.", false);
                    //         continue;
                    //     } else {
                    //         $supplier           =   Supplier::get()->filter(['Title' => $product->manufacturer])->first();
                    //         if (empty($supplier)) {
                    //             $supplier   =   Supplier::create();
                    //         }
                    //         $supplier->Title    =   $product->manufacturer;
                    //         $supplier->write();
                    //     }
                    // }
                    //
                    // $item   =   ProductPage::get()->filter(['Barcode' => $product->barcode])->first();
                    //
                    // if (empty($item)) {
                    //     $item           =   ProductPage::create();
                    //     $item->Barcode  =   $product->barcode;
                    //     $item->ParentID =   $this->landing_page->ID;
                    // }
                    //
                    // $item->Title            =   $product->title;
                    // $item->Alias            =   $product->chinese_title;
                    // $item->MeasurementUnit  =   $product->measurement;
                    // $item->StockCount       =   $product->stock_count;
                    // $item->Cost             =   $product->cost;
                    // $item->Price            =   $product->price;
                    // $item->UnitWeight       =   $product->weight;
                    //
                    // $item->write();
                    // $item->writeToStage('Live');
                    //
                    // if (!empty($supplier)) {
                    //     $item->Supplier()->add($supplier->ID);
                    //     $item->write();
                    //     $item->writeToStage('Live');
                    // }
                    //
                    // $i++;
                    // Debugger::inspect("\033[01;31m" . $item->Title . "\033[0m" . ' has been created/updated.', false);
                }

                Debugger::inspect("\033[01;31m" . $i . " items \033[0m added/created.\n\033[01;31m" . $n . " items \033[0m skipped.", false);
            }
        }

        print PHP_EOL;
    }

    private function parse_name($name)
    {
        $names  =   explode(' ', trim($name));
        $fn     =   null;
        $sn     =   null;

        if (count($names) == 2) {
            $fn =   $names[0];
            $sn =   $names[1];
        } elseif (count($names) == 1) {
            $fn =   $names[0];
        } else {
            $fn =   $name;
        }

        return [
            'fn'    =>  $fn,
            'sn'    =>  $sn
        ];
    }
}