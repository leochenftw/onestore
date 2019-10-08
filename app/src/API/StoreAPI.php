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
        'get'   =>  true,
        'post'  =>  "->isAuthenticated"
    ];

    public function get($request)
    {
        return SiteConfig::current_site_config()->getData();
    }

    public function post($request)
    {
        $siteconfig =   SiteConfig::current_site_config();
        $siteconfig->Title          =   $request->postVar('storename');
        $siteconfig->Tagline        =   $request->postVar('slogan');
        $siteconfig->GST            =   $request->postVar('gst');
        $siteconfig->StoreLocation  =   $request->postVar('location');
        $siteconfig->ContactNumber  =   $request->postVar('phone');
        $siteconfig->ContactEmail   =   $request->postVar('email');
        $siteconfig->write();

        return $siteconfig->getData();
    }
}
