<?php

namespace Leochenftw\API;
use Leochenftw\Restful\RestfulController;
use Leochenftw\Debugger;
use App\Web\Layout\ProductPage;
use SilverStripe\Versioned\Versioned;
use App\Web\Model\Expiry;
use App\Web\Model\Manufacturer;
use App\Web\Model\Supplier;
use App\Web\Layout\ProductLandingPage;
use Leochenftw\Util;
use Leochenftw\SocketEmitter;

class ProductAPI extends RestfulController
{
    private $page_size  =   50;
    /**
     * Defines methods that can be called directly
     * @var array
     */
    private static $allowed_actions = [
        'get'   =>  true,
        'post'  =>  '->isAuthenticated'
    ];

    public function get($request)
    {
        if ($id = $request->param('ID')) {
            // return $id;
            if ($id != 'All') {
                if ($product = Versioned::get_by_stage(ProductPage::class, 'Stage')->byID($id)) {
                    return $product->getData(true);
                }
                return $this->httpError(404, 'Not found');
            } elseif ($action = $request->param('Action')) {
                return $this->$action($request);
            }
        }

        $page       =   !empty($request->getVar('page')) ? $request->getVar('page') : 0;
        $sort       =   !empty($request->getVar('sort')) ? $request->getVar('sort') : 'Title';
        $by         =   !empty($request->getVar('by')) ? $request->getVar('by') : 'ASC';

        if ($supplier_id = $request->getVar('supplier')) {
            if ($supplier = Supplier::get()->byID($supplier_id)) {
                $products   =   $supplier->Products();
                $sort       =   'StockCount';
                $by         =   'ASC';
            } else {
                return $this->httpError(404, 'No such supplier');
            }
        } else {
            $products   =   ProductPage::get();
        }

        $count      =   $products->count();
        $products   =   $products->sort([$sort => $by])->limit($this->page_size, $page * $this->page_size);

        return [
            'total_page'    =>  ceil($count / $this->page_size),
            'list'          =>  $this->get_list($products)
        ];
    }

    private function get_csv_list(&$products)
    {
        $data   =   [];
        foreach ($products as $product) {
            $data[] =   [
                'Barcode'       =>  Util::null_it($product->Barcode),
                'English'       =>  Util::null_it($product->Title),
                'Chinese'       =>  Util::null_it($product->Alias),
                'Cost'          =>  Util::null_it($product->Cost),
                'Price'         =>  Util::null_it($product->Price),
                'Stock'         =>  Util::null_it($product->StockCount),
                'Supplier'      =>  implode(', ', $product->Supplier()->column('Title')),
                'Ceased'        =>  !$product->isPublished()
            ];
        }
        return $data;
    }

    private function get_list(&$products)
    {
        $data   =   [];
        foreach ($products as $product) {
            $data[] =   [
                'id'            =>  $product->ID,
                'title'         =>  $product->Title,
                'alias'         =>  $product->Alias,
                'stockcount'    =>  $product->StockCount,
                'price'         =>  $product->Price,
                'lowpoint'      =>  $product->StockLowWarningPoint,
                'updated'       =>  $product->LastEdited,
                'is_published'  =>  $product->isPublished()
            ];
        }

        return $data;
    }

    public function post($request)
    {
        if ($action = $request->param('Action')) {
            return $this->$action($request);
        }

        return $this->httpError(400, 'Missing action!');
    }

    private function download(&$request)
    {
        $products   =   Versioned::get_by_stage(ProductPage::class, 'Stage');
        $json       =   $this->get_csv_list($products);

        return Util::jsonToCsv($json, false, true);
    }

    private function edit(&$request)
    {
        $id         =   $request->param('ID');
        $product    =   empty($id) ? ProductPage::create() : Versioned::get_by_stage(ProductPage::class, 'Stage')->byID($id);

        $product->Barcode               =   Util::null_it($request->postVar('barcode'));
        $product->Title                 =   Util::null_it($request->postVar('title'));
        $product->Alias                 =   Util::null_it($request->postVar('alias'));
        $product->MeasurementUnit       =   Util::null_it($request->postVar('unit'));
        $product->StockCount            =   Util::null_it($request->postVar('stockcount'));
        $product->Cost                  =   Util::null_it($request->postVar('cost'));
        $product->Price                 =   Util::null_it($request->postVar('price'));
        $product->UnitWeight            =   Util::null_it($request->postVar('weight'));
        $product->OutOfStock            =   Util::null_it($request->postVar('outofstock'));
        $product->StockLowWarningPoint  =   Util::null_it($request->postVar('lowpoint'));
        $product->NoDiscount            =   !Util::null_it($request->postVar('discountable'));
        $product->ContributeNoPoint     =   Util::null_it($request->postVar('no_point'));

        if ($parent = ProductLandingPage::get()->first()) {
            $product->ParentID  =   $parent->ID;
        }

        if ($manufacturer_title = Util::null_it(trim($request->postVar('manufacturer')))) {
            $manufacturer   =   Manufacturer::get()->filter(['Title' => $manufacturer_title])->first();

            if (empty($manufacturer)) {
                $manufacturer           =   Manufacturer::create();
                $manufacturer->Title    =   $manufacturer_title;
                $manufacturer->write();
            }
            $product->ManufacturerID    =   $manufacturer->ID;
        }

        $product->writeToStage('Stage');

        if ($product->isPublished() || empty($id)) {
            $product->writeToStage('Live');
        }

        $product->ExpiryDates()->removeAll();

        $expiries   =   json_decode($request->postVar('expiries'));
        foreach ($expiries as $expiry) {
            $expiry_object  =   $this->get_expiry_object($expiry);
            $product->ExpiryDates()->add($expiry_object->ID);
        }

        $product->Supplier()->removeAll();

        if ($supplier_names = Util::null_it($request->postVar('supplier'))) {
            $arr    =   explode(',', $supplier_names);
            foreach ($arr as $name) {
                $name       =   trim($name);
                $supplier   =   Supplier::get()->filter(['Title' => $name])->first();

                if (empty($supplier)) {
                    $supplier   =   Supplier::create();
                    $supplier->Title    =   $name;
                    $supplier->write();
                }

                $product->Supplier()->add($supplier->ID);
                SocketEmitter::emit('new_supplier');
            }
        }

        if (empty($id)) {
            SocketEmitter::emit('product_change', [
                'id'    =>  $product->ID
            ]);
        }

        return [
            'published' =>  $product->isPublished(),
            'barcode'   =>  $product->Barcode,
            'message'   =>  'Product ' . (empty($id) ? 'added' : 'edited')
        ];
    }

    private function get_expiry_object(&$data)
    {
        $expiry =   Expiry::get()->filter(['ExpiryDate' => $data->date])->first();
        if (empty($expiry)) {
            $expiry             =   Expiry::create();
            $expiry->ExpiryDate =   $data->date;
            $expiry->write();
        }

        return $expiry;
    }

    private function cease(&$request)
    {
        $id =   $request->param('ID');
        if ($product = Versioned::get_by_stage(ProductPage::class, 'Stage')->byID($id)) {
            $product->doUnpublish();
            SocketEmitter::emit('product_change', [
                'id'    =>  $product->ID
            ]);
            return [
                'message'   =>  'Product ceased'
            ];
        }

        return $this->httpError(404, 'Not found');
    }

    private function publish(&$request)
    {
        $id =   $request->param('ID');
        if ($product = Versioned::get_by_stage(ProductPage::class, 'Stage')->byID($id)) {
            $product->publishSingle();
            SocketEmitter::emit('product_change', [
                'id'    =>  $product->ID
            ]);
            return [
                'message'   =>  'Product published'
            ];
        }

        return $this->httpError(404, 'Not found');
    }
}
