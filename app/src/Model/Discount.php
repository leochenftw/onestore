<?php

namespace App\Web\Model;

use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;

/**
 * Description
 *
 * @package silverstripe
 * @subpackage mysite
 */
class Discount extends DataObject
{
    /**
     * Defines the database table name
     * @var string
     */
    private static $table_name = 'Discount';

    /**
     * Database fields
     * @var array
     */
    private static $db = [
        'Title' =>  'Varchar(32)',
        'Type'  =>  'Enum("byPercentage,byAmount")',
        'Value' =>  'Decimal',
        'Token' =>  'Varchar(48)'
    ];

    private static $indexes = [
        'Token' =>  true
    ];

    /**
     * Event handler called before writing to the database.
     */
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        $member         =   Member::currentUser();
        $this->Token    =   substr(sha1($member->ID. '-' . $this->Type . '-' . $this->Value), 0, 16);
    }

    public function getData()
    {
        return [
            'id'    =>  $this->ID,
            'title' =>  $this->Title,
            'type'  =>  $this->Type,
            'value' =>  $this->Value
        ];
    }
}
