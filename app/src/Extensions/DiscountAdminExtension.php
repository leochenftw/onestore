<?php

namespace App\Web\Extension;
use SilverStripe\ORM\DataExtension;
use Leochenftw\eCommerce\eCollector\Model\Discount;
use App\Web\Model\Coupon;
use Leochenftw\Debugger;

class DiscountAdminExtension extends DataExtension
{
    /**
     * Managed data objects for CMS
     * @var array
     */
    private static $managed_models = [
        Discount::class,
        Coupon::class
    ];

    public function updateList(&$list)
    {
        if ($this->owner->ModelClass == Discount::class) {
            $list   =   $list->filter(['isVoucher' => false]);
        }

        return $list;
    }
}
