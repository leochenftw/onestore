<?php

namespace App\Web\Extension;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Forms\FieldList;
use Leochenftw\Debugger;
use Leochenftw\eCommerce\eCollector\Model\OrderItem;

class OrderExtension extends DataExtension
{
    private static $db = [
        'isStoreOrder'  =>  'Boolean'
    ];

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
}
