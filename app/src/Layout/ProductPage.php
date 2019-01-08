<?php

namespace App\Web\Layout;
use Page;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\CurrencyField;
use Leochenftw\eCommerce\eCollector\Model\Product;
use Leochenftw\eCommerce\eCollector\Model\OrderItem;
use Leochenftw\eCommerce\eCollector\Model\Order;
use SilverShop\HasOneField\HasOneButtonField;
use App\Web\Model\Manufacturer;
use App\Web\Model\Supplier;
use App\Web\Model\Expiry;
use Leochenftw\Grid;

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
        'StockLowWarningPoint'  =>  'Int',
        'NonDiscountable'       =>  'Boolean'
    ];

    /**
     * Defines a default list of filters for the search context
     * @var array
     */
    private static $searchable_fields = [
        'Title',
        'Alias',
        'Barcode'
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
                'outofstock'    =>  (Boolean) $this->OutOfStock,
                'lowpoint'      =>  $this->StockLowWarningPoint,
                'discountable'  =>  !$this->NonDiscountable,
                'updated'       =>  $this->LastEdited,
                'expiries'      =>  $this->ExpiryDates()->getData(),
                'is_published'  =>  $this->isPublished()
            ];
        }

        return [
            'id'            =>  $this->ID,
            'title'         =>  $this->Title,
            'price'         =>  $this->Price,
            'discountable'  =>  !$this->NonDiscountable
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
        'Manufacturer'  =>  Manufacturer::class
    ];

    /**
     * Many_many relationship
     * @var array
     */
    private static $many_many = [
        'Supplier'      =>  Supplier::class,
        'ExpiryDates'   =>  Expiry::class
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

        $fields->addFieldToTab(
            'Root.ProductDetails',
            CheckboxField::create(
                'NonDiscountable',
                'Product is not discountable'
            ),
            'UnitWeight'
        );

        $fields->addFieldsToTab(
            'Root.ProductDetails',
            [
                TextField::create('MeasurementUnit'),
                HasOneButtonField::create($this, "Manufacturer")->setDescription('This is what <strong>PRODUCES</strong> the product.')
            ]
        );

        if ($this->exists()) {
            $fields->addFieldToTab(
                'Root.Suppliers',
                Grid::make('Supplier', 'Suppliers', $this->Supplier(), false, 'GridFieldConfig_RelationEditor')
            );
        }

        return $fields;
    }
}
