<?php

namespace Leochenftw\API;
use Leochenftw\Restful\RestfulController;
use Leochenftw\Debugger;
use SilverStripe\Security\Member;
use Leochenftw\eCommerce\eCollector\Model\Order;
use Leochenftw\eCommerce\eCollector\Model\OrderItem;
use Leochenftw\eCommerce\eCollector\Model\Customer;
use App\Web\Model\Coupon;
use App\Web\Model\UseOfCoupon;
use Leochenftw\eCommerce\eCollector\Model\Discount;
use App\Web\Layout\ProductPage;
use App\Web\Model\EndDaySummary;
use Leochenftw\SocketEmitter;

class StoreOrderAPI extends RestfulController
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
        if (($list = $request->postVar('list')) && ($by = $request->postVar('by'))) {
            $list       =   json_decode($list);
            $cash_taken =   !empty($request->postVar('cash_taken')) ? $request->postVar('cash_taken') : null;
            $customer   =   null;
            $coupon     =   null;

            if (!empty($request->postVar('customer'))) {
                $customer_id    =   $request->postVar('customer');
                $customer       =   Customer::get()->byID($customer_id);
            }

            if (!empty($request->postVar('coupon'))) {
                $coupon_id      =   $request->postVar('coupon');
                $coupon         =   Coupon::get()->byID($coupon_id);
            }

            return $this->place_order($list, $by, $cash_taken, $request->postVar('discount'), $customer, $coupon);
        }

        return $this->httpError(400, 'Invalid request');
    }

    private function place_order(&$list, $by, $cash_taken = null, $discount = null, $customer = null, $coupon = null)
    {
        $order                  =   Order::create();
        $order->isStoreOrder    =   true;
        $order->PaidBy          =   $by;
        $order->Status          =   'Completed';
        if (!is_null($cash_taken)) {
            $order->CashTaken   =   $cash_taken;
        }

        if (!empty($discount)) {
            $order->DiscountEntryID =   $discount;
        }

        if (!empty($customer)) {
            $order->CustomerID  =   $customer->ID;

            if (!empty($coupon)) {
                $discount                   =   Discount::create();
                $discount->Title            =   '[CR] ' . $coupon->Title;
                $discount->DiscountBy       =   'ByValue';
                $discount->DiscountRate     =   $coupon->AmountWorth;
                $discount->Type             =   'Coupon';
                $discount->isVoucher        =   true;
                $discount->Used             =   true;
                $discount->write();

                $order->DiscountEntryID     =   $discount->ID;

                $coupon_usage               =   UseOfCoupon::create();
                $coupon_usage->CustomerID   =   $customer->ID;
                $coupon_usage->CouponID     =   $coupon->ID;
                $coupon_usage->write();
            }
        }

        $order->write();

        foreach ($list as $item) {
            $order_item             =   OrderItem::create();
            $order_item->ProductID  =   $item->id;

            if ($product = ProductPage::get()->byID($item->id)) {
                if ($product->Price != $item->unit_price) {
                    $order_item->CustomUnitPrice    =   $item->unit_price;
                }
            }

            $order_item->Quantity   =   $item->quantity;
            $order_item->isRefunded =   $item->refund;
            $order_item->OrderID    =   $order->ID;
            $order_item->write();
        }

        $order->sum_total_amount();

        if (!empty($customer)) {

            if (!empty($coupon)) {
                $customer->ShopPoints   -=  $coupon->Points;
            }

            $customer->ShopPoints   +=  $order->PointsWorth;

            $customer->write();
            $order->PointBalanceSnapshot    =   $customer->ShopPoints;
            $order->write();
        }

        EndDaySummary::cumulate($order->TotalAmount, $order->PaidBy, date('Y-m-d', strtotime($order->Created)));

        $receipt    =   $order->getData();

        SocketEmitter::emit('new_order');

        return $receipt['order'];
    }
}
