<?php

namespace App\Web\Extension;

use SilverStripe\ORM\DataExtension;

class MemberExension extends DataExtension
{
    public function getData()
    {
        return  [
            'id'            =>  $this->owner->ID,
            'first_name'    =>  $this->owner->FirstName,
            'surname'       =>  $this->owner->Surname,
            'email'         =>  $this->owner->Email
        ];
    }
}
