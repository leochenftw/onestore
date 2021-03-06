<?php

namespace Leochenftw\API;
use Leochenftw\Restful\RestfulController;
use Leochenftw\Debugger;
use Leochenftw\eCommerce\eCollector\Model\Discount;
use App\Web\Layout\ProductPage;
use Leochenftw\eCommerce\eCollector\Model\Order;
use Leochenftw\eCommerce\eCollector\Model\Customer;
use App\Web\Model\Coupon;
use App\Web\Model\UseOfCoupon;
use App\Web\Model\Supplier;
use SilverStripe\Versioned\Versioned;
use App\Web\Model\EndDaySummary;
use App\Web\Model\Expiry;

class DashboardAPI extends RestfulController
{
    /**
     * Defines methods that can be called directly
     * @var array
     */
    private static $allowed_actions = [
        'get'   =>  "->isAuthenticated"
    ];

    public function get($request)
    {
        if ($action = $request->param('Action')) {
            $action =   'get_' . $action;
            if ($this->hasMethod($action)) {
                return $this->$action();
            }
        }

        return [
            'sums'          =>  $this->get_today_sums(),
            'products'      =>  $this->get_num_products(),
            'suppliers'     =>  $this->get_num_active_suppliers(),
            'members'       =>  $this->get_num_customers(),
            'summaries'     =>  $this->get_summaries(),
            'expiries'      =>  [
                'expired'   =>  $this->get_expired_list(),
                'expiring'  =>  $this->get_expiring_list()
            ],
            'low_stocks'    =>  $this->get_stock_lows()
        ];
    }

    private function get_num_customers()
    {
        return [
            'active'    =>  Customer::get()->filter(['Suspended' => false])->count(),
            'suspended' =>  Customer::get()->filter(['Suspended' => true])->count()
        ];
    }

    private function get_num_active_suppliers()
    {
        $suppliers  =   Supplier::get();
        $n          =   0;
        $i          =   0;
        foreach ($suppliers as $supplier) {
            if ($supplier->Products()->count() > 0) {
                $n++;
            } else {
                $i++;
            }
        }

        return [
            'active'    =>  $n,
            'inactive'  =>  $i
        ];
    }

    private function get_num_products()
    {
        return [
            'trading'   =>  ProductPage::get()->count(),
            'ceased'    =>  Versioned::get_by_stage(ProductPage::class, 'Stage')->exclude(['ID' => ProductPage::get()->column('ID')])->count()
        ];
    }

    private function get_today_sums()
    {
        $filter                                 =   [];
        $today                                  =   date('Y-m-d', time());
        $filter['Created:GreaterThanOrEqual']   =   strtotime($today . 'T00:00:00');
        $filter['Created:LessThan']             =   strtotime($today . 'T23:59:59');

        $orders =   Order::get()->filter($filter);

        return [
            'trans'     =>  $orders->count(),
            'total'     =>  $orders->sum('TotalAmount'),
            'eftpos'    =>  $orders->filter(['PaidBy' => 'EFTPOS'])->sum('TotalAmount'),
            'cash'      =>  $orders->filter(['PaidBy' => 'Cash'])->sum('TotalAmount'),
            'web'       =>  $orders->filter(['PaidBy' => 'Web Order'])->sum('TotalAmount'),
            'voucher'   =>  $this->get_voucher_total($filter)
        ];
    }

    private function get_voucher_total(&$filter)
    {
        $vouchers   =   UseOfCoupon::get()->filter($filter);
        $sum        =   0;
        foreach ($vouchers as $voucher) {
            if ($voucher->Coupon()->exists()) {
                $sum    +=  $voucher->Coupon()->AmountWorth;
            }
        }
        return $sum;
    }

    private function get_summaries()
    {
        $summaries  =   EndDaySummary::get()->filter(['Date:LessThan' => date('Y-m-d', time())])->limit(7);

        return $summaries->getData();
    }

    private function get_expiring_list()
    {
        return Expiry::get()->filter(['ExpiryDate:LessThanOrEqual' => strtotime('+30 days'), 'ExpiryDate:GreaterThanOrEqual' => date('Y-m-d', time())])->getListData();
    }

    private function get_expired_list()
    {
        return Expiry::get()->filter(['ExpiryDate:LessThanOrEqual' => date('Y-m-d', time())])->getListData();
    }

    private function get_stock_lows()
    {
        $raw    =   ProductPage::get()->filter(['StockLowWarningPoint:GreaterThan' => 0]);
        $list   =   $raw->where('StockCount < StockLowWarningPoint');

        return $list->getListData();
    }
}
