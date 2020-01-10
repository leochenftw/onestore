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

class RefundAPI extends RestfulController
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
        if (($list = $request->postVar('list')) && ($receipt = $request->param('receipt'))) {
            $receipt    =   str_replace('RECEIPT-', '', $receipt);
            $list       =   json_decode($list);
            if ($order = Order::get()->filter(['ReceiptNumber' => $receipt])->first()) {
                $customer           =   $order->Customer()->exists() ? $order->Customer() : null;

                $amount             =   0;
                $factor             =   1;
                $nondiscountable    =   0;
                $points             =   0;
                $nondispoints       =   0;

                if ($order->DiscountEntry()->exists() && $order->DiscountEntry()->DiscountBy == 'ByPercentage') {
                    $factor     -=  ($order->DiscountEntry()->DiscountRate * 0.01);
                }

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

                    $subtotal   =   $order_item->Subtotal;
                    $subpoints  =   $order_item->PointsWorth;

                    if ($order_item->Product()->exists() && !$order_item->Product()->NoDiscount) {
                        $amount     +=  ($subtotal * $factor * ($order_item->isRefunded ? -1 : 1));
                        $points     +=  $subpoints * $factor;
                    } else {
                        $nondiscountable    +=  $subtotal * ($order_item->isRefunded ? -1 : 1);
                        $nondispoints       +=  $subpoints;
                    }
                }

                if ($order->DiscountEntry()->exists() && $order->DiscountEntry()->DiscountBy == 'ByValue') {
                    $amount -=  $order->DiscountEntry()->DiscountRate;
                    $amount =   $amount < 0 ? 0 : $amount;
                    $points -=  $order->DiscountEntry()->DiscountRate;
                    $points =   $points < 0 ? 0 : $points;
                }

                $order->sum_total_amount();

                if (!empty($customer)) {
                    $customer->ShopPoints           +=  ($points + $nondispoints);
                    $customer->write();
                    $order->PointBalanceSnapshot    =   $customer->ShopPoints;
                    $order->write();
                }

                EndDaySummary::cumulate($amount + $nondiscountable, $order->PaidBy, date('Y-m-d', strtotime($order->Created)));

                $receipt    =   $order->getData();

                SocketEmitter::emit('new_order', !empty($customer) ? [
                    'id'        =>  $customer->ID,
                    'points'    =>  $customer->ShopPoints
                ] : []);

                return $receipt['order'];
            }

            return $this->httpError(404, 'No such transaction!');
        }

        return $this->httpError(400, 'Invalid request');
    }
}
