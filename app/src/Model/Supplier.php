<?php

namespace App\Web\Model;

use SilverStripe\ORM\DataObject;
use App\Web\Layout\ProductPage;
use App\Web\Extension\TitleAliasExtension;

/**
 * Description
 *
 * @package silverstripe
 * @subpackage mysite
 */
class Supplier extends DataObject
{
    /**
     * Defines the database table name
     * @var string
     */
    private static $table_name = 'Supplier';

    /**
     * Database fields
     * @var array
     */
    private static $db = [
        'Memo'  =>  'Text'
    ];

    /**
     * Defines extension names and parameters to be applied
     * to this object upon construction.
     * @var array
     */
    private static $extensions = [
        TitleAliasExtension::class
    ];

    /**
     * Has_many relationship
     * @var array
     */
    private static $has_many = [
        'Products'  =>  ProductPage::class
    ];
}
