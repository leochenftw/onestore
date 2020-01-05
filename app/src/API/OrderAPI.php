<?php

namespace Leochenftw\API;
use Leochenftw\Restful\RestfulController;
use Leochenftw\Debugger;
use SilverStripe\Versioned\Versioned;
use App\Web\Model\Expiry;
use Leochenftw\eCommerce\eCollector\Model\Order;
use Leochenftw\eCommerce\eCollector\Model\Customer;
use Leochenftw\Util;
use App\Web\Model\UseOfCoupon;

class OrderAPI extends RestfulController
{
    private $page_size  =   50;
    /**
     * Defines methods that can be called directly
     * @var array
     */
    private static $allowed_actions = [
        'get'   =>  true,
        'post'  =>  '->isAuthenticated'
    ];

    public function get($request)
    {
        if ($id = $request->param('ID')) {
            if ($id != 'All') {
                if ($order = Order::get()->byID($id)) {
                    return $order->getData();
                }
            } elseif ($action = $request->param('Action')) {
                return $this->$action($request);
            }

            return $this->httpError(404, 'Not found');
        }

        $page   =   !empty($request->getVar('page')) ? $request->getVar('page') : 0;
        $sort   =   !empty($request->getVar('sort')) ? $request->getVar('sort') : 'Created';
        $by     =   !empty($request->getVar('by')) ? $request->getVar('by') : 'DESC';

        if ($customer_id = $request->getVar('customer')) {
            if ($customer = Customer::get()->byID($customer_id)) {
                $orders     =   $customer->Orders();
                $count      =   $orders->count();
                $sum        =   $orders->sum('TotalAmount');
                $split_sum  =   [
                    'eftpos'    =>  $orders->filter(['PaidBy' => 'EFTPOS'])->sum('TotalAmount'),
                    'cash'      =>  $orders->filter(['PaidBy' => 'Cash'])->sum('TotalAmount'),
                    'voucher'   =>  $this->get_voucher_total(['CustomerID' => $customer_id])
                ];
                $orders     =   $orders->sort([$sort => $by])->limit($this->page_size, $page * $this->page_size);

                return [
                    'total_page'    =>  ceil($count / $this->page_size),
                    'total_items'   =>  $count,
                    'list'          =>  $orders->getListData(),
                    'sum'           =>  $sum,
                    'split_sum'     =>  $split_sum
                ];
            }

            return $this->httpError(404, 'Not such customer');
        }

        $filter =   [];

        if ($from = $request->getVar('from')) {
            $filter['Created:GreaterThanOrEqual']   =   strtotime($from . 'T00:00:00');
        }

        if ($to = $request->getVar('to')) {
            $filter['Created:LessThan'] =   strtotime($to . 'T23:59:59');
        }

        if (empty($filter)) {
            $today                                  =   date('Y-m-d', time());
            $filter['Created:GreaterThanOrEqual']   =   strtotime($today . 'T00:00:00');
            $filter['Created:LessThan']             =   strtotime($today . 'T23:59:59');
        }

        if (!empty($request->getVar('discount_only'))) {
            $filter['DiscountEntryID:not']  =   0;
        }

        $orders =   Order::get();

        if (!empty($filter)) {
            $orders =   $orders->filter($filter);
        }

        $count      =   $orders->count();
        $sum        =   $orders->sum('TotalAmount');
        $split_sum  =   [
            'eftpos'    =>  $orders->filter(['PaidBy' => 'EFTPOS'])->sum('TotalAmount'),
            'cash'      =>  $orders->filter(['PaidBy' => 'Cash'])->sum('TotalAmount'),
            'voucher'   =>  $this->get_voucher_total($filter)
        ];

        $orders =   $orders->sort([$sort => $by])->limit($this->page_size, $page * $this->page_size);

        return [
            'total_page'    =>  ceil($count / $this->page_size),
            'total_items'   =>  $count,
            'list'          =>  $orders->getListData(),
            'sum'           =>  $sum,
            'split_sum'     =>  $split_sum
        ];
    }

    private function get_voucher_total($filter)
    {
        unset($filter['DiscountEntryID:not']);
        $vouchers   =   UseOfCoupon::get()->filter($filter);
        $sum        =   0;
        foreach ($vouchers as $voucher) {
            if ($voucher->Coupon()->exists()) {
                $sum    +=  $voucher->Coupon()->AmountWorth;
            }
        }
        return $sum;
    }

    public function post($request)
    {
        if ($action = $request->param('Action')) {
            return $this->$action($request);
        } elseif ($receipt = $request->param('ID')) {
            if ($order = Order::get()->filter(['ReceiptNumber' => $receipt])->first()) {
                return [
                    'total_page'    =>  0,
                    'list'          =>  [
                        [
                            'id'                =>  $order->ID,
                            'receipt'           =>  $order->ReceiptNumber,
                            'datetime'          =>  $order->Created,
                            'amount'            =>  $order->TotalAmount,
                            'item_count'        =>  $order->getTotalItems(),
                            'payment_method'    =>  $order->PaidBy,
                            'by'                =>  $order->Operator()->exists() ? $order->Operator()->Title : 'Anonymous'
                        ]
                    ],
                    'sum'           =>  $order->TotalAmount
                ];
            }
        }

        return $this->httpError(400, 'Missing action!');
    }

    private function bind_customer(&$request)
    {
        if ($id = $request->param('ID')) {

            if ($order = Order::get()->byID($id)) {
                if ($customer_id = $request->postVar('customer_id')) {
                    if ($customer = Customer::get()->byID($customer_id)) {
                        $customer->ShopPoints           +=  $order->TotalAmount;
                        $customer->write();

                        $order->CustomerID              =   $customer->ID;
                        $order->PointBalanceSnapshot    =   $customer->ShopPoints;
                        $order->write();

                        $customer_data                  =   $customer->getData();
                        $customer_data['shop_points']   =   number_format($order->PointBalanceSnapshot, 0);
                        return $customer_data;
                    }

                    return $this->httpError(404, 'Customer not found');
                }
            }

            return $this->httpError(404, 'Transaction not found');
        }

        return $this->httpError(400, 'Missing Transaction ID');
    }

    private function download(&$request)
    {
        $filter =   [];

        if ($from = $request->getVar('from')) {
            $filter['Created:GreaterThanOrEqual']   =   strtotime($from);
        }

        if ($to = $request->getVar('to')) {
            $filter['Created:LessThan'] =   strtotime($to . 'T23:59:59');
        }

        if (empty($filter)) {
            $today                                  =   date('Y-m-d', time());
            $filter['Created:GreaterThanOrEqual']   =   strtotime($today . 'T00:00:00');
            $filter['Created:LessThan']             =   strtotime($today . 'T23:59:59');
        }

        $orders =   Order::get()->filter($filter)->sort(['ID' => 'DESC']);

        $json   =   $this->get_csv_list($orders);
        return Util::jsonToCsv($json, false, true);
    }

    private function get_csv_list(&$orders)
    {
        $data   =   [];
        foreach ($orders as $order) {
            $data[] =   [
                'Receipt No.'       =>  $order->ReceiptNumber,
                'Timestamp'         =>  $order->Created,
                'Amount'            =>  $order->TotalAmount,
                'Item Count'        =>  $order->getTotalItems(),
                'Payment Method'    =>  $order->PaidBy,
                'Operator'          =>  $order->Operator()->exists() ? $order->Operator()->Title : 'Anonymous'
            ];
        }

        return $data;
    }

    public function Backoff()
    {
        return 'ffs';
    }
}
