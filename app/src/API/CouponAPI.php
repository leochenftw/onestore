<?php

namespace Leochenftw\API;
use App\Web\Model\UseOfCoupon;
use Leochenftw\Restful\RestfulController;
use Leochenftw\Debugger;
use App\Web\Model\Coupon;

class CouponAPI extends RestfulController
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
            if ($voucher = Coupon::get()->byID($id)) {
                return $voucher->getData();
            }

            return $this->httpError(404, 'Not found');
        }

        return Coupon::get()->getData();;
    }

    public function post($request)
    {
        if ($action = $request->param('Action')) {
            return $this->$action($request);
        }

        return $this->httpError(400, 'Missing action!');
    }

    private function create_coupon(&$request)
    {
        $coupon                 =   Coupon::create();
        $coupon->Title          =   $request->postVar('label');
        $coupon->Points         =   $request->postVar('points');
        $coupon->AmountWorth    =   $request->postVar('worth');
        $coupon->write();

        return $coupon->getData();
    }

    private function update(&$request)
    {
        $id =   $request->param('ID');
        if ($coupon = Coupon::get()->byID($id)) {
            $coupon->Title          =   $request->postVar('label');
            $coupon->Points         =   $request->postVar('points');
            $coupon->AmountWorth    =   $request->postVar('worth');
            $coupon->write();

            return $coupon->getData();
        }

        return $this->httpError(404, 'Not found');
    }

    private function delete(&$request)
    {
        $id =   $request->param('ID');
        if ($coupon = Coupon::get()->byID($id)) {
            if (UseOfCoupon::get()->filter(['CouponID' => $id])->count() == 0) {
                $coupon->delete();
            } else{
                $coupon->Ceased =   true;
                $coupon->write();
            }

            return true;
        }

        return $this->httpError(404, 'Not found');
    }
}
