<?php

namespace App\Web\Model;
use Leochenftw\eCommerce\eCollector\Model\Customer;
use SilverStripe\ORM\DataObject;

/**
 * Description
 *
 * @package silverstripe
 * @subpackage mysite
 */
class UseOfCoupon extends DataObject
{
    /**
     * Defines the database table name
     * @var string
     */
    private static $table_name = 'UseOfCoupon';

    /**
     * Has_one relationship
     * @var array
     */
    private static $has_one = [
        'Customer'  =>  Customer::class,
        'Coupon'    =>  Coupon::class
    ];

    /**
     * Defines summary fields commonly used in table columns
     * as a quick overview of the data for this dataobject
     * @var array
     */
    private static $summary_fields = [
        'Coupon.Title'          =>  'Voucher',
        'Coupon.AmountWorth'    =>  'Worth',
        'Created'               =>  'Used at'
    ];
}
