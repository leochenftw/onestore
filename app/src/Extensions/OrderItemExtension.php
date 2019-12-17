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
        'isRefunded'    =>  'Boolean'
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

        if ($this->owner->Product()->exists()) {
            $this->owner->Subtotal  =   $this->owner->Quantity * $this->owner->Product()->Price;
            $this->owner->Subweight +=  $this->owner->Quantity * $this->owner->Product()->UnitWeight;
        } elseif ($this->owner->Membership()->exists()) {
            $this->owner->Subtotal  =   $this->owner->Quantity * $this->owner->Membership()->Price;
        }
    }

    public function getData()
    {
        if ($this->owner->Product()->exists()) {
            $data               =   $this->owner->Product()->getData();
            $data['prod_id']    =   $data['id'];
            $data['id']         =   $this->owner->ID;
            $data['quantity']   =   $this->owner->Quantity;
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
        return  $this->owner->Product()->exists() ?
                '$' . money_format('%i',  $this->owner->Product()->Price) :
                (
                    $this->owner->Membership()->exists() ?
                    '$' . money_format('%i',  $this->owner->Membership()->Price) :
                    '-'
                );
    }
}
