<?php

namespace Leochenftw\API;
use Leochenftw\Restful\RestfulController;
use Leochenftw\Debugger;
use App\Web\Layout\ProductPage;
use SilverStripe\Versioned\Versioned;
use SilverStripe\Security\Member;
use App\Web\Layout\ProductLandingPage;

class MemberAPI extends RestfulController
{
    /**
     * Defines methods that can be called directly
     * @var array
     */
    private static $allowed_actions = [
        'get'   =>  "->isAuthenticated",
        'post'  =>  '->isAuthenticated'
    ];

    public function get($request)
    {
        $member =   null;
        if ($id = $request->param('ID')) {

        }

        if (empty($member)) {
            $member =   Member::currentUser();
        }

        return $member->getData();
    }

    public function post($request)
    {
        $action =   $request->param('Action');
        if (empty($action)) {
            $action =   'update';
        }

        return $this->$action($request);
    }

    private function update($request)
    {
        if ($member = Member::get()->byID($request->param('ID'))) {
            $member->FirstName  =   $request->postVar('firstname');
            $member->Surname    =   $request->postVar('surname');
            $member->Email      =   $request->postVar('email');
            if ($pass = $request->postVar('pass')) {
                $member->Password   =   $pass;
            }

            $member->write();

            return $member->getData();
        }

        return $this->httpError(404, 'No such member');
    }
}
