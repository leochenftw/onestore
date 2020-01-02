<?php

namespace App\Web\Extension;
use SilverStripe\ORM\DataExtension;
use App\Web\Model\UseOfCoupon;

class CustomerExension extends DataExtension
{
    /**
     * Database fields
     * @var array
     */
    private static $db = [
        'Suspended'     =>  'Boolean',
        'PhoneNumber'   =>  'Varchar(48)',
        'ShopPoints'    =>  'Decimal',
        'Wechat'        =>  'Varchar(128)'
    ];

    private static $indexes = [
        'PhoneNumber'   =>  [
            'type'  =>  'unique'
        ]
    ];

    /**
     * Has_many relationship
     * @var array
     */
    private static $has_many = [
        'CouponUsages'  =>  UseOfCoupon::class
    ];

    public function getData()
    {
        return [
            'id'            =>  $this->owner->ID,
            'first_name'    =>  $this->owner->FirstName,
            'surname'       =>  $this->owner->Surname,
            'phone'         =>  $this->owner->PhoneNumber,
            'wechat'        =>  $this->owner->Wechat,
            'email'         =>  $this->sanitised_email(),
            'shop_points'   =>  floor($this->owner->ShopPoints),
            'date_joined'   =>  $this->owner->Created
        ];
    }

    public function getListData()
    {
        return $this->owner->getData();
    }

    private function sanitised_email()
    {
        if ($this->owner->Email == $this->owner->PhoneNumber . '@' . $this->owner->PhoneNumber . '.com' ||
            $this->owner->Email == $this->owner->PhoneNumber) {
            return null;
        }

        return $this->owner->Email;
    }
}
