<?php

namespace App\Web\Extensions;

use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\EmailField;
use SilverStripe\Forms\TextField;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Assets\Image;

/**
 * @file SiteConfigExtension
 *
 * Extension to provide Open Graph tags to site config.
 */
class SiteConfigExtension extends DataExtension
{
    /**
     * Database fields
     * @var array
     */
    private static $db = [
        'GST'           =>  'Varchar(32)',
        'StoreLocation' =>  'Text',
        'ContactNumber' =>  'Varchar(16)',
        'ContactEmail'  =>  'Varchar(256)'
    ];
    /**
     * Has_one relationship
     * @var array
     */
    private static $has_one = [
        'Logo'  =>  Image::class
    ];

    /**
     * Update Fields
     * @return FieldList
     */
    public function updateCMSFields(FieldList $fields)
    {
        $owner = $this->owner;
        $fields->addFieldToTab(
            'Root.Main',
            UploadField::create(
                'Logo',
                'Logo'
            ),
            'Title'
        );
        $fields->addFieldsToTab(
            'Root.Main',
            [
                TextField::create('GST'),
                TextField::create('ContactNumber', 'Store Phone Number'),
                EmailField::create('ContactEmail', 'Store Email'),
                TextareaField::create('StoreLocation', 'Store Location')
            ]
        );
        return $fields;
    }

    public function getData()
    {
        $logo   =   $this->owner->Logo();
        return [
            'logo'      =>  $logo->exists() ? $logo->ScaleHeight(80)->getAbsoluteURL() : null,
            'title'     =>  $this->owner->Title,
            'slogan'    =>  $this->owner->Tagline,
            'gst'       =>  $this->owner->GST,
            'phone'     =>  $this->owner->ContactNumber,
            'email'     =>  $this->owner->ContactEmail,
            'location'  =>  $this->owner->StoreLocation
        ];
    }
}
