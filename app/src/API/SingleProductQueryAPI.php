<?php

namespace Leochenftw\API;
use Leochenftw\Restful\RestfulController;
use Leochenftw\Debugger;
use App\Web\Layout\ProductPage;
use SilverStripe\Versioned\Versioned;

class SingleProductQueryAPI extends RestfulController
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
            if ($product = Versioned::get_by_stage(ProductPage::class, 'Stage')->filter(['Barcode' => $term])->first()) {
                return $product->getData(true);
            }

            return $this->httpError(404, 'No result found');
        }

        return $this->httpError(400, 'Missing search term');
    }
}
