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
            if ($this->hasMethod($action)) {
                return $this->$action();
            }
        }

        return [
            'sums'          =>  $this->get_today_sums(),
            'products'      =>  $this->get_num_products(),
            'suppliers'     =>  $this->get_num_active_suppliers(),
            'members'       =>  $this->get_num_customers()
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
}
