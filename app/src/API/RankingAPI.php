<?php

namespace Leochenftw\API;
use Leochenftw\Restful\RestfulController;
use Leochenftw\Debugger;
use Leochenftw\eCommerce\eCollector\Model\OrderItem;
use SilverStripe\ORM\DB;
use App\Web\Model\Supplier;

class RankingAPI extends RestfulController
{
    private $page_size  =   50;
    /**
     * Defines methods that can be called directly
     * @var array
     */
    private static $allowed_actions = [
        'get'   =>  '->isAuthenticated'
    ];

    public function get($request)
    {
        $page       =   !empty($request->getVar('page')) ? $request->getVar('page') : 0;
        $sort       =   !empty($request->getVar('sort')) ? $request->getVar('sort') : 'Product.Title';
        $by         =   !empty($request->getVar('by')) ? $request->getVar('by') : 'ASC';

        $type       =   $request->getVar('type');
        $start      =   $request->getVar('start');
        $end        =   $request->getVar('end');

        $filters    =   [];
        $orderitems =   OrderItem::get();

        if ($term = $request->getVar('term')) {
            if ($type == 'barcode') {
                $filters['Product.Barcode'] =   $term;
            } elseif ($type == 'supplier') {
                $suppliers  =   Supplier::get()->filter(['Title:StartsWith' => $term])->column('ID');
                if (!empty($suppliers)) {
                    $products   =   DB::query('SELECT "ProductPageID" FROM "ProductPage_Supplier" WHERE "SupplierID" in (' . implode(',', $suppliers) . ')')->column('ProductPageID');
                    $orderitems =   $orderitems->filter(['ProductID' => $products]);
                } else {
                    return [
                        'total_page'    =>  0,
                        'list'          =>  []
                    ];
                }
            }
        }

        if (!empty($start)) {
            $filters['Created:GreaterThanOrEqual']  =   $start . 'T00:00:00';
        }

        if (!empty($end)) {
            $filters['Created:LessThanOrEqual']     =   $end . 'T23:59:59';
        }

        if (!empty($filters)) {
            $orderitems =   $orderitems->filter($filters);
        }

        $list       =   [];

        if ($sort == 'Quantity') {
            $list       =   $this->get_list($orderitems);
            $count      =   count($list);
            $qty        =   array_column($list, 'quantity');
            array_multisort($qty, ($by == 'ASC' ? SORT_ASC : SORT_DESC), $list);
        } elseif ($sort == 'Subtotal') {
            $list       =   $this->get_list($orderitems);
            $count      =   count($list);
            $amount     =   array_column($list, 'amount');
            array_multisort($amount, ($by == 'ASC' ? SORT_ASC : SORT_DESC), $list);
        } else {
            $orderitems =   $orderitems->sort([$sort => $by]);
            $list       =   $this->get_list($orderitems);
            $count      =   count($list);
        }

        if (!empty($list)) {
            $list       =   array_slice($list, $page * $this->page_size, $this->page_size);
        }

        return [
            'total_page'    =>  ceil($count / $this->page_size),
            'list'          =>  $list
        ];
    }

    private function get_list(&$orderitems)
    {
        $data   =   [];
        foreach ($orderitems as $orderitem) {
            if (empty($data[$orderitem->Product()->Barcode])) {
                $data[$orderitem->Product()->Barcode]   =   [
                    'barcode'   =>  $orderitem->Product()->Barcode,
                    'product'   =>  $orderitem->Product()->Title,
                    'suppliers' =>  implode(', ', $orderitem->Product()->Supplier()->column('Title')),
                    'amount'    =>  0,
                    'quantity'  =>  0
                ];
            }

            $data[$orderitem->Product()->Barcode]['quantity']   +=  $orderitem->Quantity;
            $data[$orderitem->Product()->Barcode]['amount']     +=  $orderitem->Subtotal;
        }

        $data   =   array_values($data);

        return $data;
    }
}
