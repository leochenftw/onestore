<?php

namespace App\Web\Extension;

use SilverStripe\ORM\DataExtension;
use SilverStripe\Forms\FieldList;
use SilverStripe\Security\Member;
use Leochenftw\Debugger;
use Leochenftw\eCommerce\eCollector\Model\Discount;
use Leochenftw\eCommerce\eCollector\Model\OrderItem;

class OrderExtension extends DataExtension
{
    private static $db = [
        'isStoreOrder'          =>  'Boolean',
        'PaidBy'                =>  'Enum("Cash,EFTPOS,Voucher,Web Order")',
        'ReceiptNumber'         =>  'Varchar(36)',
        'CashTaken'             =>  'Currency',
        'ItemCount'             =>  'Int',
        'PointBalanceSnapshot'  =>  'Int',
        'PointsWorth'           =>  'Decimal',
        'Migrated'              =>  'Boolean',
    ];

    /**
     * Defines summary fields commonly used in table columns
     * as a quick overview of the data for this dataobject
     * @var array
     */
    private static $summary_fields = [
        'ID'            =>  'ID',
        'ReceiptNumber' =>  'Receipt#',
        'TotalAmount'   =>  'Amount'
    ];

    /**
     * Defines a default list of filters for the search context
     * @var array
     */
    private static $searchable_fields = [
        'ReceiptNumber'
    ];

    /**
     * Has_one relationship
     * @var array
     */
    private static $has_one = [
        'DiscountEntry' =>  Discount::class,
        'Operator'      =>  Member::class
    ];

    private static $indexes =   [
        'ReceiptNumber' =>  true
    ];

    /**
     * Event handler called before writing to the database.
     */
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        $this->owner->ReceiptNumber =   substr($this->owner->MerchantReference, 0, 10);
        if (empty($this->owner->OperatorID) && Member::currentUser()) {
            $this->owner->OperatorID    =   Member::currentUser()->ID;
        }
    }

    /**
     * Event handler called after writing to the database.
     */
    public function onAfterWrite()
    {
        parent::onAfterWrite();

        $n  =   $this->getTotalItems();

        if ($this->owner->ItemCount != $n) {
            $this->owner->ItemCount =   $n;
            $this->owner->write();
        }
    }

    public function getTotalItems()
    {
        $n  =   0;

        foreach ($this->owner->Items() as $item) {
            $n += $item->Quantity;
        }
        $n  =   round($n * 100) * 0.01;
        return $n == ((int) $n) ? $n : number_format($n, 2);
    }

    public function add_to_cart($product_id, $qty)
    {
        if ($existing_item = $this->owner->Items()->filter(['ProductID' => $product_id])->first()) {
            $existing_item->Quantity    +=  $qty;
            $existing_item->write();
        } else {
            $item               =   OrderItem::create();
            $item->ProductID    =   $product_id;
            $item->Quantity     =   $qty;
            $item->OrderID      =   $this->owner->ID;
            $item->write();
        }
    }

    public function sum_total_amount()
    {
        $amount             =   0;
        $factor             =   1;
        $nondiscountable    =   0;
        $points             =   0;
        $nondispoints       =   0;

        if ($this->owner->DiscountEntry()->exists() && $this->owner->DiscountEntry()->DiscountBy == 'ByPercentage') {
            $factor     -=  ($this->owner->DiscountEntry()->DiscountRate * 0.01);
        }

        foreach ($this->owner->Items() as $item) {
            $subtotal   =   $item->Subtotal;
            $subpoints  =   $item->PointsWorth;

            if ($item->Product()->exists() && !$item->Product()->NoDiscount) {
                $amount     +=  ($subtotal * $factor * ($item->isRefunded ? -1 : 1));
                $points     +=  $subpoints * $factor;
            } else {
                $nondiscountable    +=  $subtotal * ($item->isRefunded ? -1 : 1);
                $nondispoints       +=  $subpoints;
            }
        }

        if ($this->owner->DiscountEntry()->exists() && $this->owner->DiscountEntry()->DiscountBy == 'ByValue') {
            $amount -=  $this->owner->DiscountEntry()->DiscountRate;
            $amount =   $amount < 0 ? 0 : $amount;
            $points -=  $this->owner->DiscountEntry()->DiscountRate;
            $points =   $points < 0 ? 0 : $points;
        }

        $this->owner->TotalAmount       =   $amount + $nondiscountable;
        $this->owner->PointsWorth       =   $points + $nondispoints;
        $this->owner->write();
    }

    public function getData()
    {
        $customer   =   $this->owner->Customer()->exists() ? $this->owner->Customer()->getData() : null;

        if (!empty($customer)) {
            $customer['shop_points']    =   number_format(floor($this->owner->PointBalanceSnapshot), 0);
        }

        return [
            'goods'     =>  $this->loop_items(),
            'discount'  =>  $this->owner->DiscountEntry()->exists() ? $this->owner->DiscountEntry()->getData() : null,
            'order'     =>  [
                'amount'        =>  $this->owner->TotalAmount,
                'at'            =>  $this->owner->LastEdited,
                'by'            =>  $this->owner->Operator()->exists() ? $this->owner->Operator()->Title : 'Anonymous',
                'barcode'       =>  'RECEIPT-' . $this->owner->ReceiptNumber,
                'method'        =>  $this->owner->PaidBy,
                'cash'          =>  $this->owner->CashTaken,
                'shop_points'   =>  $this->owner->PointsWorth,
                'customer'      =>  $customer
            ]
        ];
    }

    public function getListData()
    {
        return [
            'id'                =>  $this->owner->ID,
            'receipt'           =>  $this->owner->ReceiptNumber,
            'datetime'          =>  $this->owner->Created,
            'amount'            =>  $this->owner->TotalAmount,
            'item_count'        =>  $this->getTotalItems(),
            'payment_method'    =>  $this->owner->PaidBy,
            'discount'          =>  $this->owner->DiscountEntry()->exists() ? $this->owner->DiscountEntry()->Title : null,
            'customer'          =>  $this->owner->Customer()->exists() ? $this->owner->Customer()->Title : null,
            'by'                =>  $this->owner->Operator()->exists() ? $this->owner->Operator()->Title : 'Anonymous'
        ];
    }

    private function loop_items()
    {
        $data   =   [];
        foreach ($this->owner->Items() as $item) {
            if ($item_data = $item->getData()) {
                $item_data['order_item_id'] =   $item_data['id'];
                $item_data['id']            =   $item_data['prod_id'];

                $data[] =   $item_data;
            }
        }

        return $data;
    }

    public function jsonSerialize()
    {
        return [
            'ID' => $this->owner->MerchantReference,
            'CustomerID' => $this->owner->CustomerID,
            'LegacyID' => $this->owner->ID,
            'Created' => $this->owner->Created,
            'Status' => $this->owner->Status,
            'AnonymousCustomer' => $this->owner->AnonymousCustomer,
            'TotalAmount' => $this->owner->TotalAmount,
            'TotalWeight' => $this->owner->TotalWeight,
            'PayableTotal' => $this->owner->PayableTotal,
            'Email' => $this->owner->Email,
            'Phone' => $this->owner->Phone,
            'ShippingFirstname' => $this->owner->ShippingFirstname,
            'ShippingSurname' => $this->owner->ShippingSurname,
            'ShippingAddress' => $this->owner->ShippingAddress,
            'ShippingOrganisation' => $this->owner->ShippingOrganisation,
            'ShippingApartment' => $this->owner->ShippingApartment,
            'ShippingSuburb' => $this->owner->ShippingSuburb,
            'ShippingTown' => $this->owner->ShippingTown,
            'ShippingRegion' => $this->owner->ShippingRegion,
            'ShippingCountry' => $this->owner->ShippingCountry,
            'ShippingPostcode' => $this->owner->ShippingPostcode,
            'ShippingPhone' => $this->owner->ShippingPhone,
            'SameBilling' => $this->owner->SameBilling,
            'BillingFirstname' => $this->owner->BillingFirstname,
            'BillingSurname' => $this->owner->BillingSurname,
            'BillingAddress' => $this->owner->BillingAddress,
            'BillingOrganisation' => $this->owner->BillingOrganisation,
            'BillingApartment' => $this->owner->BillingApartment,
            'BillingSuburb' => $this->owner->BillingSuburb,
            'BillingTown' => $this->owner->BillingTown,
            'BillingRegion' => $this->owner->BillingRegion,
            'BillingCountry' => $this->owner->BillingCountry,
            'BillingPostcode' => $this->owner->BillingPostcode,
            'BillingPhone' => $this->owner->BillingPhone,
            'Comment' => $this->owner->Comment,
            'TrackingNumber' => $this->owner->TrackingNumber,
            'PaidBy' => $this->owner->PaidBy,
            'ReceiptNumber' => $this->owner->ReceiptNumber,
            'CashTaken' => $this->owner->CashTaken,
            'ItemCount' => $this->owner->ItemCount,
            'PointBalanceSnapshot' => $this->owner->PointBalanceSnapshot,
            'PointsWorth' => $this->owner->PointsWorth,
            'OrderItems' => array_map(fn ($item) => [
                'ProductID' => $item->ProductID,
                'Quantity' => $item->Quantity,
                'Subtotal' => $item->Subtotal,
                'Subweight' => $item->Subweight,
                'isRefunded' => $item->isRefunded,
                'PayableTotal' => $item->PayableTotal,
                'UnitPrice' => round($item->Subtotal * 100 / $item->Quantity) * 0.01,
                'CustomUnitPrice' => $item->CustomUnitPrice,
                'isRefunded' => $item->isRefunded,
                'PointsWorth' => $item->PointsWorth,
            ], $this->owner->Items()->toArray()),
            'OrderPayments' => array_map(fn ($payment) => [
                'PaymentMethod' => $payment->PaymentMethod,
                'CardType' => $payment->CardType,
                'CardNumber' => $payment->CardNumber,
                'PayerAccountNumber' => $payment->PayerAccountNumber,
                'PayerAccountSortCode' => $payment->PayerAccountSortCode,
                'PayerBankName' => $payment->PayerBankName,
                'CardHolder' => $payment->CardHolder,
                'Expiry' => $payment->Expiry,
                'TransacID' => $payment->TransacID,
                'Status' => $payment->Status,
                'Amount' => $payment->Amount,
                'Message' => $payment->Message,
                'IP' => $payment->IP,
            ], $this->owner->Payments()->toArray()),
        ];
    }
}
