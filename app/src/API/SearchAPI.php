<?php

namespace Leochenftw\API;
use Leochenftw\Restful\RestfulController;
use Leochenftw\Debugger;
use App\Web\Layout\ProductPage;
use SilverStripe\Versioned\Versioned;

class SearchAPI extends RestfulController
{
    private $page_size  =   50;
    /**
     * Defines methods that can be called directly
     * @var array
     */
    private static $allowed_actions = [
        'get'   =>  false,
        'post'  =>  '->isAuthenticated'
    ];

    public function post($request)
    {
        if ($term = $request->postVar('term')) {
            $barcode_result     =   Versioned::get_by_stage(ProductPage::class, 'Stage')->filter(['Barcode' => $term]);
            if ($barcode_result->count() > 0) {
                return [
                    'total_page'    =>  0,
                    'list'          =>  $this->get_list($barcode_result)
                ];
            }

            $page               =   $request->postVar('page');
            $sort               =   !empty($request->postVar('sort')) ? $request->postVar('sort') : 'Title';
            $by                 =   !empty($request->postVar('by')) ? $request->postVar('by') : 'ASC';
            $title_result       =   ProductPage::get()->filterAny(['Title:PartialMatch' => $term, 'Alias:PartialMatch' => $term]);

            if ($title_result->count() > 0) {
                $count          =   $title_result->count();
                $title_result   =   $title_result->sort([$sort => $by])->limit($this->page_size, $page * $this->page_size);
                return [
                    'total_page'    =>  ceil($count / $this->page_size),
                    'list'          =>  $this->get_list($title_result)
                ];
            }

            return $this->httpError(404, 'No result found');

        }

        return $this->httpError(404, 'Missing search term');
    }

    private function get_list(&$products)
    {
        $data   =   [];
        foreach ($products as $product) {
            $data[] =   [
                'id'            =>  $product->ID,
                'title'         =>  $product->Title,
                'alias'         =>  $product->Alias,
                'stockcount'    =>  $product->StockCount,
                'price'         =>  $product->Price,
                'lowpoint'      =>  $product->StockLowWarningPoint,
                'updated'       =>  $product->LastEdited,
                'is_published'  =>  $product->isPublished()
            ];
        }

        return $data;
    }
}
