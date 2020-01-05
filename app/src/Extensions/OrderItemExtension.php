<?php

namespace App\Web\Extension;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Forms\FieldList;
use App\Web\Layout\ProductPage;

class OrderItemExtension extends DataExtension
{
    /**
     * Database fields
     * @var array
     */
    private static $db = [
        'CustomUnitPrice'   =>  'Currency',
        'isRefunded'        =>  'Boolean',
        'PointsWorth'       =>  'Decimal'
    ];
    /**
     * Has_one relationship
     * @var array
     */
    private static $has_one = [
        'Product'       =>  ProductPage::class
    ];
    /**
     * Defines summary fields commonly used in table columns
     * as a quick overview of the data for this dataobject
     * @var array
     */
    private static $summary_fields = [
        'makeTitle'         =>  'Product',
        'isRefundedItem'    =>  'is refunded Item',
        'UnitPrice'         =>  'Unit Price',
        'Quantity'          =>  'Quantity',
        'Subtotal'          =>  'Subtotal'
    ];

    public function makeTitle()
    {
        if ($this->owner->Product()->exists()) {
            return $this->owner->Product()->Title;
        } elseif ($this->owner->Membership()->exists()) {
            return $this->owner->Membership()->Title;
        }

        return '-';
    }

    public function isRefundedItem()
    {
        return $this->owner->isRefunded ? 'Yes' : 'No';
    }

    /**
     * Event handler called before writing to the database.
     */
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        if (!empty($this->owner->CustomUnitPrice)) {
            $this->owner->Subtotal  =   $this->owner->Quantity * $this->owner->CustomUnitPrice;
        } elseif ($this->owner->Product()->exists()) {
            $this->owner->Subtotal  =   $this->owner->Quantity * $this->owner->Product()->Price;
        }

        if ($this->owner->Product()->exists() && !$this->owner->Product()->ContributeNoPoint) {
            $this->owner->PointsWorth   =   $this->owner->Subtotal * ($this->owner->isRefunded ? -1 : 1);
        }

        $this->owner->Subweight +=  $this->owner->Quantity * $this->owner->Product()->UnitWeight;
    }

    public function getData()
    {
        if ($this->owner->Product()->exists()) {
            $data               =   $this->owner->Product()->getData();

            if (!empty($this->owner->CustomUnitPrice)) {
                $data['price'] =   $this->owner->CustomUnitPrice;
            }

            $n  =   round($this->owner->Quantity * 100) * 0.01;
            $n  =   $n == ((int) $n) ? $n : number_format($n, 2);

            $data['prod_id']    =   $data['id'];
            $data['id']         =   $this->owner->ID;
            $data['quantity']   =   $n;
            $data['refund']     =   $this->owner->isRefunded;
            return $data;
        }

        return null;
    }

    /**
     * Event handler called after writing to the database.
     */
    public function onAfterWrite()
    {
        parent::onAfterWrite();

        if ($this->owner->Order()->exists() && $this->owner->Order()->Status == 'Pending') {
            $this->owner->Order()->UpdateAmountWeight();
        }

        if ($this->owner->Product()->exists()) {
            if ($this->owner->isRefunded) {
                $this->owner->Product()->StockCount += $this->owner->Quantity;
            } else {
                $this->owner->Product()->StockCount -= $this->owner->Quantity;
            }
            $this->owner->Product()->write();
            $this->owner->Product()->writeToStage('Live');
        }
    }

    public function UnitPrice()
    {
        return  !empty($this->owner->CustomUnitPrice) ?
                ('$' . money_format('%i',  $this->owner->CustomUnitPrice)) :
                ($this->owner->Product()->exists() ?
                '$' . money_format('%i',  $this->owner->Product()->Price) : 'N/A');

    }
}
