<?php

namespace Leochenftw\API;
use Leochenftw\Restful\RestfulController;
use Leochenftw\Debugger;
use SilverStripe\Security\Member;
use SilverStripe\Security\IdentityStore;
use SilverStripe\Core\Injector\Injector;

class SignoutAPI extends RestfulController
{
    /**
     * Defines methods that can be called directly
     * @var array
     */
    private static $allowed_actions = [
        'post'   =>  "->isAuthenticated"
    ];

    public function post($request)
    {
        if ($member = Member::currentUser()) {
            Injector::inst()->get(IdentityStore::class)->logOut();
            return  [
                'message'   =>  'Signed out'
            ];
        }

        return $this->httpError(400, 'Please sign in first!');
    }
}
