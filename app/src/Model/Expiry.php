<?php

namespace App\Web\Model;

use SilverStripe\ORM\DataObject;
use App\Web\Layout\ProductPage;

/**
 * Description
 *
 * @package silverstripe
 * @subpackage mysite
 */
class Expiry extends DataObject
{
    /**
     * Defines the database table name
     * @var string
     */
    private static $table_name = 'Expiry';

    /**
     * Database fields
     * @var array
     */
    private static $db = [
        'ExpiryDate'    =>  'Date'
    ];

    private static $indexes = [
        'ExpiryDate'    =>  true
    ];

    /**
     * Default sort ordering
     * @var array
     */
    private static $default_sort = ['ExpiryDate' => 'ASC'];

    /**
     * Belongs_many_many relationship
     * @var array
     */
    private static $belongs_many_many = [
        'Products'  =>  ProductPage::class
    ];

    public function getData()
    {
        return [
            'id'    =>  $this->ID,
            'date'  =>  $this->ExpiryDate
        ];
    }

    public function getListData()
    {
        return [
            'id'        =>  $this->ID,
            'date'      =>  $this->ExpiryDate,
            'products'  =>  $this->get_product_list()
        ];
    }

    private function get_product_list()
    {
        $dict   =   $this->Products()->map()->toArray();
        $list   =   [];

        foreach ($dict as $key => $value) {
            $list[] =   [
                'id'    =>  $key,
                'title' =>  $value
            ];
        }

        return $list;
    }
}
