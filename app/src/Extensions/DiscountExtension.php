<?php

namespace App\Web\Extension;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;
use Leochenftw\eCommerce\eCollector\Model\Order;

class DiscountExtension extends DataExtension
{
    /**
     * Database fields
     * @var array
     */
    private static $db = [
        'isVoucher' =>  'Boolean'
    ];

    /**
     * Has_many relationship
     * @var array
     */
    private static $has_many = [
        'Orders'    =>  Order::class
    ];

    /**
     * Update Fields
     * @return FieldList
     */
    public function updateCMSFields(FieldList $fields)
    {
        $owner = $this->owner;
        $fields->removeByName([
            'Root.Main.isVoucher'
        ]);
        return $fields;
    }

    public function CustomGetData(&$data)
    {
        $data['type']       =   'discount';
        $data['created']    =   strtotime($this->owner->Created);
        return $data;
    }
}
