<?php

namespace Leochenftw\API;
use Leochenftw\Restful\RestfulController;
use Leochenftw\Debugger;
use App\Web\Model\Discount;
use App\Web\Layout\ProductPage;

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
        if (strpos($lookup, 'DISCOUNT-') === 0) {
            $lookup =   str_replace('DISCOUNT-', '', $lookup);
            if ($discount = Discount::get()->filter(['Token' => $lookup])->first()) {
                return [
                    'type'  =>  'discount',
                    'data'  =>  $discount->getData()
                ];
            }

            return $this->httpError(404, 'Discount not found!');
        } else if ($product = ProductPage::get()->filter(['Barcode' => $lookup])->first()) {
            return [
                'type'  =>  'product',
                'data'  =>  $product->getData()
            ];
        }

        return $this->httpError(404, 'Product not found!');
    }
}
