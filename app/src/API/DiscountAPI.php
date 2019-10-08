<?php

namespace Leochenftw\API;
use Leochenftw\Restful\RestfulController;
use Leochenftw\Debugger;
use App\Web\Layout\ProductPage;
use SilverStripe\Versioned\Versioned;
use App\Web\Model\Discount;
use App\Web\Layout\ProductLandingPage;

class DiscountAPI extends RestfulController
{
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
            return $this->httpError(404, 'Not found');
        }

        $discounts  =   Discount::get();
        return $discounts->getData();
    }

    public function post($request)
    {
        if ($action = $request->param('Action')) {
            return $this->$action($request);
        }

        return $this->httpError(400, 'Missing action!');
    }

    private function edit(&$request)
    {
        $id         =   $request->param('ID');
        $product    =   empty($id) ? ProductPage::create() : Versioned::get_by_stage(ProductPage::class, 'Stage')->byID($id);

        $product->Barcode               =   $request->postVar('barcode');
        $product->Title                 =   $request->postVar('title');
        $product->Alias                 =   $request->postVar('alias');
        $product->MeasurementUnit       =   $request->postVar('unit');
        $product->StockCount            =   $request->postVar('stockcount');
        $product->Cost                  =   $request->postVar('cost');
        $product->Price                 =   $request->postVar('price');
        $product->UnitWeight            =   $request->postVar('weight');
        $product->OutOfStock            =   $request->postVar('outofstock');
        $product->StockLowWarningPoint  =   $request->postVar('lowpoint');
        $product->NonDiscountable       =   $request->postVar('discountable') == 'true' ? 0 : 1;

        if ($parent = ProductLandingPage::get()->first()) {
            $product->ParentID  =   $parent->ID;
        }

        $product->writeToStage('Stage');

        if ($product->isPublished()) {
            $product->writeToStage('Live');
        }

        $product->ExpiryDates()->removeAll();

        $expiries   =   json_decode($request->postVar('expiries'));
        foreach ($expiries as $expiry) {
            $expiry_object  =   $this->get_expiry_object($expiry);
            $product->ExpiryDates()->add($expiry_object->ID);
        }

        // $product->dd    =   ;

        // 'weight'        =>  $this->,
        // 'outofstock'    =>  $this->,
        // 'lowpoint'      =>  $this->,
        // 'discountable'  =>  !$this->,
        // 'updated'       =>  $this->LastEdited,
        // 'expiries'      =>  $this->()->getData(),
        // 'is_published'  =>  $this->isPublished()

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
            return [
                'message'   =>  'Product published'
            ];
        }

        return $this->httpError(404, 'Not found');
    }
}
