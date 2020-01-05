<?php

namespace App\Web\Model;
use Leochenftw\eCommerce\eCollector\Model\Order;
use SilverStripe\ORM\DataObject;

/**
 * Description
 *
 * @package silverstripe
 * @subpackage mysite
 */
class EndDaySummary extends DataObject
{
    /**
     * Defines the database table name
     * @var string
     */
    private static $table_name = 'EndDaySummary';

    /**
     * Database fields
     * @var array
     */
    private static $db = [
        'Date'      =>  'Date',
        'Total'     =>  'Currency',
        'EFTPOS'    =>  'Currency',
        'Cash'      =>  'Currency'
    ];

    /**
     * Defines summary fields commonly used in table columns
     * as a quick overview of the data for this dataobject
     * @var array
     */
    private static $summary_fields = [
        'Date'      =>  'Date',
        'EFTPOS'    =>  'EFTPOS',
        'Cash'      =>  'Cash',
        'Total'     =>  'Total'
    ];

    public function populateDefaults()
    {
        $this->Date =   date('Y-m-d', time());
    }

    /**
     * Event handler called before writing to the database.
     */
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        $this->Total    =   $this->EFTPOS + $this->Cash;
    }

    public function self_update()
    {
        $list   =   Order::get()->filter([
            'Created:GreaterThanOrEqual' => strtotime($this->Date . 'T00:00:00'),
            'Created:LessThan' => strtotime($this->Date . 'T23:59:59')
        ]);

        $eftpos =   $list->filter(['PaidBy' => 'EFTPOS'])->sum('TotalAmount');
        $cash   =   $list->filter(['PaidBy' => 'Cash'])->sum('TotalAmount');

        $this->EFTPOS   =   $eftpos;
        $this->Cash     =   $cash;

        return $this->write();
    }

    public static function generate_by_date($date)
    {
        $list   =   Order::get()->filter([
            'Created:GreaterThanOrEqual' => strtotime($date . 'T00:00:00'),
            'Created:LessThan' => strtotime($date . 'T23:59:59')
        ]);

        $eftpos =   $list->filter(['PaidBy' => 'EFTPOS'])->sum('TotalAmount');
        $cash   =   $list->filter(['PaidBy' => 'Cash'])->sum('TotalAmount');

        $sum    =   EndDaySummary::create();

        $sum->Date      =   $date;
        $sum->EFTPOS    =   $eftpos;
        $sum->Cash      =   $cash;

        return $sum->write();
    }
}
