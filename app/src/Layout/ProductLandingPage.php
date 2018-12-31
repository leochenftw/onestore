<?php

namespace App\Web\Layout;
use SilverStripe\Versioned\Versioned;
use SilverStripe\Lumberjack\Model\Lumberjack;
use App\Web\Extensions\LumberjackExtension;
use Page;

/**
 * Description
 *
 * @package silverstripe
 * @subpackage mysite
 */
class ProductLandingPage extends Page
{
    private static $description = 'The page that holds all products. You can only have one product landing page at any one time';
    /**
     * Defines the database table name
     * @var string
     */
    private static $table_name = 'ProductLandingPage';
    /**
     * Defines the allowed child page types
     * @var array
     */
    private static $allowed_children = [
        ProductPage::class
    ];

    /**
     * Defines extension names and parameters to be applied
     * to this object upon construction.
     * @var array
     */
    private static $extensions = [
        Lumberjack::class,
        LumberjackExtension::class
    ];

    /**
     * DataObject create permissions
     * @param Member $member
     * @param array $context Additional context-specific data which might
     * affect whether (or where) this object could be created.
     * @return boolean
     */
    public function canCreate($member = null, $context = [])
    {
        return Versioned::get_by_stage(__CLASS__, 'Stage')->count() == 0;
    }
}
