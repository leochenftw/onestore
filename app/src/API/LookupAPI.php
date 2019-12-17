<?php

namespace Leochenftw\API;
use Leochenftw\Restful\RestfulController;
use Leochenftw\Debugger;
use Leochenftw\eCommerce\eCollector\Model\Discount;
use App\Web\Layout\ProductPage;
use Leochenftw\eCommerce\eCollector\Model\Order;

class LookupAPI extends RestfulController
{
    /**
     * Defines methods that can be called directly
     * @var array
     */
    private static $allowed_actions = [
        'post'   =>  "->isAuthenticated"
    ];

    public function post($request)
    {
        $lookup =   $request->postVar('input');
        if (strpos($lookup, 'DCNT-') === 0) {
            $lookup =   str_replace('DCNT-', '', $lookup);
            if ($discount = Discount::get()->filter(['CouponCode' => $lookup])->first()) {
                return [
                    'type'  =>  'discount',
                    'data'  =>  $discount->getData()
                ];
            }

            return $this->httpError(404, 'Discount not found!');
        } elseif (strpos($lookup, 'RECEIPT-') === 0) {
            $lookup =  str_replace('RECEIPT-', '', $lookup);
            if ($order = Order::get()->filter(['ReceiptNumber' => $lookup])->first()) {
                return [
                    'type'  =>  'receipt',
                    'data'  =>  $order->getData()
                ];
            }
        } elseif ($product = ProductPage::get()->filter(['Barcode' => $lookup])->first()) {
            return [
                'type'  =>  'product',
                'data'  =>  $product->getData()
            ];
        }

        return $this->httpError(404, 'Product not found!');
    }
}
