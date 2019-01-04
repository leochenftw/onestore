<?php

namespace Leochenftw\API;
use Leochenftw\Restful\RestfulController;
use Leochenftw\Debugger;
use SilverStripe\Security\Member;
use Leochenftw\eCommerce\eCollector\Model\Order;
use Leochenftw\eCommerce\eCollector\Model\OrderItem;

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
            if (!empty($request->postVar('discount'))) {
                return $this->place_order($list, $by, $request->postVar('discount'));
            }
            return $this->place_order($list, $by);
        }

        return $this->httpError(400, 'Invalid request');
    }

    private function place_order(&$list, $by, $discount = null)
    {
        $order                  =   Order::create();
        $order->isStoreOrder    =   true;
        $order->PaidBy          =   $by;
        $order->Status          =   'Completed';
        if (!empty($discount)) {
            $order->DiscountID  =   $discount;
        }

        $order->write();

        foreach ($list as $item) {
            $order_item             =   OrderItem::create();
            $order_item->ProductID  =   $item->id;
            $order_item->Quantity   =   $item->quantity;
            $order_item->isRefunded =   $item->refund;
            $order_item->OrderID    =   $order->ID;
            $order_item->write();
        }

        $order->sum_total_amount();

        return [
            'at'        =>  $order->LastEdited,
            'by'        =>  $order->Operator()->exists() ? $order->Operator()->Title : 'Anonymous',
            'barcode'   =>  'RECEIPT-' . $order->ReceiptNumber
        ];
    }
}
