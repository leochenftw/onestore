<?php

namespace Leochenftw\API;
use Leochenftw\Restful\RestfulController;
use SilverStripe\SiteConfig\SiteConfig;
use Leochenftw\Debugger;
use SilverStripe\Security\Member;

class StoreAPI extends RestfulController
{
    /**
     * Defines methods that can be called directly
     * @var array
     */
    private static $allowed_actions = [
        'get'   =>  true
    ];

    public function get($request)
    {
        return SiteConfig::current_site_config()->getData();
    }
}
