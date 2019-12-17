<?php

namespace Leochenftw\API;
use Leochenftw\Restful\RestfulController;
use SilverStripe\SiteConfig\SiteConfig;
use Leochenftw\Debugger;
use SilverStripe\Security\Member;
use SilverStripe\Assets\Folder;
use SilverStripe\Assets\Image;
use SilverStripe\AssetAdmin\Controller\AssetAdmin;

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
        $siteconfig                 =   SiteConfig::current_site_config();
        $siteconfig->Title          =   $request->postVar('storename');
        $siteconfig->Tagline        =   $request->postVar('slogan');
        $siteconfig->GST            =   $request->postVar('gst');
        $siteconfig->StoreLocation  =   $request->postVar('location');
        $siteconfig->ContactNumber  =   $request->postVar('phone');
        $siteconfig->ContactEmail   =   $request->postVar('email');

        if ($logo = $request->postVar('logo')) {
            $siteconfig->LogoID     =   $this->handle_logo($logo['tmp_name'], $logo['name']);
        }

        $siteconfig->write();

        return $siteconfig->getData();
    }

    private function handle_logo($image, $filename)
    {
        $fold           =   Folder::find_or_make('Logos');
        $img            =   Image::create();

        $img->setFromLocalFile($image, $this->getFilename($filename));
        $img->ParentID  =   $fold->ID;
        $img->write();
        AssetAdmin::create()->generateThumbnails($img);
        $img->publishSingle();
        return $img->ID;
    }

    private function getFilename($src)
    {
        $seg        =   explode('/', $src);
        $name       =   strtolower($seg[count($seg) - 1]);
        $name_seg   =   explode('.', $name);
        $ext        =   $name_seg[count($name_seg) - 1];
        array_pop($name_seg);

        foreach ($name_seg as &$part) {
            $part   =   str_replace('%20', '-', $part);
            $part   =   str_replace(' ', '-', $part);
        }

        return implode('-', $name_seg) . '.' . $ext;
    }
}
