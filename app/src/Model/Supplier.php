<?php

namespace App\Web\Model;

use SilverStripe\ORM\DataObject;
use App\Web\Layout\ProductPage;
use App\Web\Extension\TitleAliasExtension;
use SilverStripe\GraphQL\Scaffolding\Interfaces\ScaffoldingProvider;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\SchemaScaffolder;
use Leochenftw\SocketEmitter;

/**
 * Description
 *
 * @package silverstripe
 * @subpackage mysite
 */
class Supplier extends DataObject implements ScaffoldingProvider
{
    /**
     * Singular name for CMS
     * @var string
     */
    private static $singular_name = 'Supplier';
    /**
     * Plural name for CMS
     * @var string
     */
    private static $plural_name = 'Suppliers';
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
        'Email' =>  'Varchar(256)',
        'Phone' =>  'Varchar(16)',
        'Memo'  =>  'Text'
    ];

    public function getMiniData()
    {
        return [
            'id'    =>  $this->ID,
            'title' =>  $this->Title,
            'alias' =>  $this->Alias
        ];
    }

    /**
     * Defines extension names and parameters to be applied
     * to this object upon construction.
     * @var array
     */
    private static $extensions = [
        TitleAliasExtension::class
    ];

    /**
     * Belongs_many_many relationship
     * @var array
     */
    private static $belongs_many_many = [
        'Products'  =>  ProductPage::class
    ];

    public function NumProducts()
    {
        return $this->Products()->count();
    }

    public function provideGraphQLScaffolding(SchemaScaffolder $scaffolder)
    {
        return $scaffolder;
    }
}
