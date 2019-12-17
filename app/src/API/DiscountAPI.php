<?php

namespace Leochenftw\API;
use Leochenftw\eCommerce\eCollector\Model\OrderItem;
use Leochenftw\eCommerce\eCollector\Model\Order;
use Leochenftw\Restful\RestfulController;
use Leochenftw\Debugger;
use App\Web\Layout\ProductPage;
use SilverStripe\Versioned\Versioned;
use Leochenftw\eCommerce\eCollector\Model\Discount;
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
            if ($discount = Discount::get()->byID($id)) {
                return $discount->getData();
            }
            return $this->httpError(404, 'Not found');
        }

        $discounts  =   Discount::get()->filter(['Used' => false]);
        return $discounts->getData();
    }

    public function post($request)
    {
        if ($action = $request->param('Action')) {
            return $this->$action($request);
        }

        return $this->httpError(400, 'Missing action!');
    }

    private function create_discount(&$request)
    {
        $discount               =   Discount::create();
        $discount->Title        =   $request->postVar('label');
        $discount->DiscountBy   =   $request->postVar('type') == 'by_percentage' ? 'ByPercentage' : 'ByValue';
        $discount->DiscountRate =   $request->postVar('rate');
        $discount->InfiniteUse  =   true;
        $discount->write();

        return $discount->getData();
    }

    private function update(&$request)
    {
        $id =   $request->param('ID');
        if ($discount = Discount::get()->byID($id)) {
            $discount->Title        =   $request->postVar('label');
            $discount->DiscountBy   =   $request->postVar('type') == 'by_percentage' ? 'ByPercentage' : 'ByValue';
            $discount->DiscountRate =   $request->postVar('rate');
            $discount->write();

            return $discount->getData();
        }

        return $this->httpError(404, 'Not found');
    }

    private function delete(&$request)
    {
        $id =   $request->param('ID');
        if ($discount = Discount::get()->byID($id)) {
            if (Order::get()->filter(['DiscountEntryID' => $id])->count() == 0 &&
                OrderItem::get()->filter(['DiscountID' => $id])->count() == 0) {
                $discount->delete();
            } else{
                $discount->InfiniteUse  =   false;
                $discount->Used         =   true;
                $discount->write();
            }

            return true;
        }

        return $this->httpError(404, 'Not found');
    }
}
