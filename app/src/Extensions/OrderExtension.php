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
        'PaidBy'                =>  'Enum("Cash,EFTPOS,Voucher")',
        'ReceiptNumber'         =>  'Varchar(36)',
        'CashTaken'             =>  'Currency',
        'ItemCount'             =>  'Int',
        'PointBalanceSnapshot'  =>  'Int',
        'PointsWorth'           =>  'Decimal'
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
                $amount +=  ($subtotal * $factor * ($item->isRefunded ? -1 : 1));
                $points +=  $subpoints * $factor;
            } else {
                $nondiscountable    +=  $subtotal * ($item->isRefunded ? -1 : 1);
                $points             +=  $subpoints;
            }
        }

        if ($this->owner->DiscountEntry()->exists() && $this->owner->DiscountEntry()->DiscountBy == 'ByValue') {
            $amount -=  $this->owner->DiscountEntry()->DiscountRate;
            $amount =   $amount < 0 ? 0 : $amount;
            $points -=  $this->owner->DiscountEntry()->DiscountRate;
        }

        $this->owner->TotalAmount   =   $amount + $nondiscountable;
        $this->owner->PointsWorth   =   $points;
        $this->owner->write();
    }

    public function getData()
    {
        $customer   =   $this->owner->Customer()->exists() ? $this->owner->Customer()->getData() : null;

        if (!empty($customer)) {
            $customer['shop_points']    =   number_format($this->owner->PointBalanceSnapshot, 0);
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
                $data[] =   $item_data;
            }
        }

        return $data;
    }
}
