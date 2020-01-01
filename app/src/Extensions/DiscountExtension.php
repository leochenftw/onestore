<?php

namespace App\Web\Extension;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;

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
