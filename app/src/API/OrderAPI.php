<?php

namespace Leochenftw\API;
use Leochenftw\Restful\RestfulController;
use Leochenftw\Debugger;
use SilverStripe\Versioned\Versioned;
use App\Web\Model\Expiry;
use Leochenftw\eCommerce\eCollector\Model\Order;
use Leochenftw\Util;

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
        $filter =   [];

        if ($from = $request->getVar('from')) {
            $filter['Created:GreaterThanOrEqual']   =   strtotime($from);
        }

        if ($to = $request->getVar('to')) {
            $filter['Created:LessThan'] =   strtotime($to . 'T23:59:59');
        }

        $orders =   Order::get();

        if (!empty($filter)) {
            $orders =   $orders->filter($filter);
        }

        $count  =   $orders->count();
        $orders =   $orders->sort([$sort => $by])->limit($this->page_size, $page * $this->page_size);

        return [
            'total_page'    =>  ceil($count / $this->page_size),
            'list'          =>  $orders->getListData(),
            'sum'           =>  $orders->sum('TotalAmount')
        ];
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

    private function download(&$request)
    {
        $orders =   Order::get();
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
}
