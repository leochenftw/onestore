<?php

namespace App\Web\Extension;

use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;

class TitleAliasExtension extends DataExtension
{
    /**
     * Database fields
     * @var array
     */
    private static $db = [
        'Title' =>  'Varchar(128)',
        'Alias' =>  'Varchar(64)'
    ];

    private static $indexes = [
        'Title' =>  true,
        'Alias' =>  true
    ];
}
