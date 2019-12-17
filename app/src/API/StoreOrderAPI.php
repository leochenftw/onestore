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
            $cash_taken =   !empty($request->postVar('cash_taken')) ? $request->postVar('cash_taken') : null;
            if (!empty($request->postVar('discount'))) {
                return $this->place_order($list, $by, $cash_taken, $request->postVar('discount'));
            }
            return $this->place_order($list, $by, $cash_taken);
        }

        return $this->httpError(400, 'Invalid request');
    }

    private function place_order(&$list, $by, $cash_taken = null, $discount = null)
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
            'barcode'   =>  'RECEIPT-' . $order->ReceiptNumber,
            'cash'      =>  $order->CashTaken
        ];
    }
}
