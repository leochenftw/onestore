<?php

namespace App\Web\Model;
use Leochenftw\eCommerce\eCollector\Model\Discount;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataObject;

/**
 * Description
 *
 * @package silverstripe
 * @subpackage mysite
 */
class Coupon extends DataObject
{
    /**
     * Singular name for CMS
     * @var string
     */
    private static $singular_name = 'Coupon';
    /**
     * Plural name for CMS
     * @var string
     */
    private static $plural_name = 'Coupons';
    /**
     * Defines the database table name
     * @var string
     */
    private static $table_name = 'Coupon';

    /**
     * Database fields
     * @var array
     */
    private static $db = [
        'Title'         =>  'Varchar(128)',
        'Points'        =>  'Int',
        'AmountWorth'   =>  'Decimal',
        'Ceased'        =>  'Boolean'
    ];

    /**
     * Has_many relationship
     * @var array
     */
    private static $has_many = [
        'UsedLog'       =>  UseOfCoupon::class
    ];

    /**
     * CMS Fields
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->fieldByName('Root.Main.Points')->setDescription('The points that is required to acquire this coupon');
        return $fields;
    }

    public static function getAvailableCoupons(&$customer)
    {
        if (!empty($customer) && !empty($customer->ShopPoints)) {
            return Coupon::get()->filter(['Points:LessThanOrEqual' => $customer->ShopPoints, 'Ceased' => false])->getData();
        }

        return [];
    }

    public function getListData()
    {
        return [
            'id'        =>  $this->ID,
            'title'     =>  $this->Title,
            'by'        =>  '-',
            'rate'      =>  $this->AmountWorth,
            'type'      =>  'voucher',
            'points'    =>  $this->Points,
            'created'   =>  strtotime($this->Created)
        ];
    }

    public function getData()
    {
        return [
            'id'        =>  $this->ID,
            'title'     =>  $this->Title,
            'worth'     =>  $this->AmountWorth,
            'points'    =>  $this->Points,
            'type'      =>  'voucher',
            'created'   =>  strtotime($this->Created)
        ];
    }

    public function getDynamoDbMapping()
    {
        return [
            'Class' => 'Coupon',
            'ID' => "Coupon-{$this->ID}",
            'Title' => $this->Title,
        ];
    }
}
