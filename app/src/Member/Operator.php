<?php

namespace App\Web\Member;
use SilverStripe\Security\Member;
use SilverStripe\GraphQL\Scaffolding\Interfaces\ScaffoldingProvider;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\SchemaScaffolder;

/**
 * Description
 *
 * @package silverstripe
 * @subpackage mysite
 */
class Operator extends Member implements ScaffoldingProvider
{
    /**
     * Defines the database table name
     * @var string
     */
    private static $table_name = 'Operator';
    /**
     * Database fields
     * @var array
     */
    private static $db = [
        'Suspended' =>  'Boolean'
    ];

    /**
     * Defines summary fields commonly used in table columns
     * as a quick overview of the data for this dataobject
     * @var array
     */
    private static $summary_fields = [
        'FirstName' =>  'First Name',
        'Surname'   =>  'Surname',
        'Email'     =>  'Email',
        'Suspended' =>  'is Suspended?'
    ];

    public function provideGraphQLScaffolding(SchemaScaffolder $scaffolder)
    {
        return $scaffolder;
    }
}
