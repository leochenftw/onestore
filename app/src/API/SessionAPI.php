<?php

namespace Leochenftw\API;
use Leochenftw\Restful\RestfulController;
use Leochenftw\Debugger;
use SilverStripe\Security\Member;
use SilverStripe\Security\SecurityToken;

class SessionAPI extends RestfulController
{
    /**
     * Defines methods that can be called directly
     * @var array
     */
    private static $allowed_actions = [
        'get'   =>  "->isAuthenticated"
    ];

    public function get($request)
    {
        $member =   Member::currentUser();
        return  [
            'id'            =>  $member->ID,
            'first_name'    =>  $member->FirstName,
            'surname'       =>  $member->Surname,
            'email'         =>  $member->Email,
            'csrf'          =>  SecurityToken::inst()->getValue()
        ];
    }
}
