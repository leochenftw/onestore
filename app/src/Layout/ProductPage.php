<?php

namespace App\Web\Layout;
use Page;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\CurrencyField;
use Leochenftw\eCommerce\eCollector\Model\Product;
use Leochenftw\eCommerce\eCollector\Model\OrderItem;
use Leochenftw\eCommerce\eCollector\Model\Order;
use SilverShop\HasOneField\HasOneButtonField;
use App\Web\Model\Manufacturer;
use App\Web\Model\Supplier;
/**
 * Description
 *
 * @package silverstripe
 * @subpackage mysite
 */
class ProductPage extends Product
{
    /**
     * Defines whether a page can be in the root of the site tree
     * @var boolean
     */
    private static $can_be_root = false;
    /**
     * Defines the database table name
     * @var string
     */
    private static $table_name = 'ProductPage';
    private static $show_in_sitetree = false;
    private static $allowed_children = [];

    /**
     * Database fields
     * @var array
     */
    private static $db = [
        'Barcode'               =>  'Varchar(128)',
        'Alias'                 =>  'Varchar(64)',
        'MeasurementUnit'       =>  'Varchar(8)',
        'StockCount'            =>  'Int',
        'Cost'                  =>  'Currency',
        'StockLowWarningPoint'  =>  'Int'
    ];

    public function getData($full = false)
    {
        if ($full) {
            return [
                'id'            =>  $this->ID,
                'barcode'       =>  $this->Barcode,
                'title'         =>  $this->Title,
                'alias'         =>  $this->Alias,
                'unit'          =>  $this->MeasurementUnit,
                'stockcount'    =>  $this->StockCount,
                'cost'          =>  $this->Cost,
                'price'         =>  $this->Price,
                'weight'        =>  $this->UnitWeight,
                'outofstock'    =>  $this->OutOfStock,
                'lowpoint'      =>  $this->StockLowWarningPoint
            ];
        }

        return [
            'id'    =>  $this->ID,
            'title' =>  $this->Title,
            'price' =>  $this->Price
        ];
    }

    private static $indexes = [
        'Alias'     =>  true,
        'Barcode'   =>  [
            'type'      =>  'unique',
            'columns'   =>  ['Barcode']
        ]
    ];

    /**
     * Has_one relationship
     * @var array
     */
    private static $has_one = [
        'Manufacturer'  =>  Manufacturer::class,
        'Supplier'      =>  Supplier::class
    ];

    /**
     * Has_many relationship
     * @var array
     */
    private static $has_many = [
        'OrderItems'    =>  'Leochenftw\eCommerce\eCollector\Model\OrderItem.Product'
    ];

    /**
     * CMS Fields
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->addFieldsToTab(
            'Root.ProductDetails',
            [
                TextField::create('Barcode'),
                TextField::create('StockCount'),
                TextField::create('StockLowWarningPoint', 'Stock Low Point')
                    ->setDescription('System will warn you when the stock level is equal or lower than this point')
            ],
            'SKU'
        );

        $fields->removeByName([
            'isDigital',
            'SKU'
        ]);

        $fields->addFieldToTab(
            'Root.ProductDetails',
            CurrencyField::create('Cost'),
            'Price'
        );

        $fields->addFieldsToTab(
            'Root.ProductDetails',
            [
                TextField::create('MeasurementUnit'),
                HasOneButtonField::create($this, "Manufacturer")->setDescription('This is what <strong>PRODUCES</strong> the product.'),
                HasOneButtonField::create($this, "Supplier")->setDescription('This is who <strong>SUPPLIES</strong> you the product.')
            ]
        );

        return $fields;
    }
}
