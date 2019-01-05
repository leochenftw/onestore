<?php

namespace App\Web\Extension;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Forms\FieldList;
use SilverStripe\Security\Member;
use Leochenftw\Debugger;
use App\Web\Model\Discount;
use Leochenftw\eCommerce\eCollector\Model\OrderItem;

class OrderExtension extends DataExtension
{
    private static $db = [
        'isStoreOrder'  =>  'Boolean',
        'PaidBy'        =>  'Enum("Cash,EFTPOS")',
        'ReceiptNumber' =>  'Varchar(36)',
        'CashTaken'     =>  'Currency'
    ];

    /**
     * Has_one relationship
     * @var array
     */
    private static $has_one = [
        'Discount'  =>  Discount::class,
        'Operator'  =>  Member::class
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

        if ($this->owner->Discount()->exists() && $this->owner->Discount()->Type == 'byPercentage') {
            $factor     -=  ($this->owner->Discount()->Value * 0.01);
        }

        foreach ($this->owner->Items() as $item) {
            $subtotal   =   $item->Subtotal;

            if ($item->Product()->exists() && !$item->Product()->NonDiscountable) {
                $amount +=  ($subtotal * $factor * ($item->isRefunded ? -1 : 1));
            } else {
                $nondiscountable    +=  $subtotal * ($item->isRefunded ? -1 : 1);
            }
        }

        if ($this->owner->Discount()->exists() && $this->owner->Discount()->Type == 'byAmount') {
            $amount -=  $this->owner->Discount()->Value;
            $amount =   $amount < 0 ? 0 : $amount;
        }

        $this->owner->TotalAmount  =   $amount + $nondiscountable;
        $this->owner->write();
    }

    public function getData()
    {
        return [
            'goods'     =>  $this->loop_items(),
            'discount'  =>  $this->owner->Discount()->exists() ? $this->owner->Discount()->getData() : null,
            'order'     =>  [
                'at'        =>  $this->owner->LastEdited,
                'by'        =>  $this->owner->Operator()->exists() ? $this->owner->Operator()->Title : 'Anonymous',
                'barcode'   =>  'RECEIPT-' . $this->owner->ReceiptNumber,
                'method'    =>  $this->owner->PaidBy,
                'cash'      =>  $this->owner->CashTaken
            ]
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
