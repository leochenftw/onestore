<?php

namespace Leochenftw\API;
use Leochenftw\Restful\RestfulController;
use Leochenftw\Debugger;
use SilverStripe\Security\Member;

class ProductAPI extends RestfulController
{
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

        return [];
    }

    public function post($request)
    {
        Debugger::inspect($request->params());
    }
}
