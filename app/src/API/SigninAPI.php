<?php

namespace Leochenftw\API;
use Leochenftw\Restful\RestfulController;
use Leochenftw\Debugger;
use SilverStripe\Security\Member;
use SilverStripe\Security\IdentityStore;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Security\PasswordEncryptor;

class SigninAPI extends RestfulController
{
    /**
     * Defines methods that can be called directly
     * @var array
     */
    private static $allowed_actions = [
        'post'   =>  true
    ];

    public function post($request)
    {
        if (($email = $request->postVar('email')) && ($pass = $request->postVar('pass'))) {
            if ($member = Member::get()->filter(['Email' => $email])->first()) {
                $encryptor  =   PasswordEncryptor::create_for_algorithm($member->PasswordEncryption);
                if ($encryptor->check($member->Password, $pass, $member->Salt, $member)) {
                    Injector::inst()->get(IdentityStore::class)->logIn($member, true);
                    return  [
                        'message'   =>  'Sign in success!'
                    ];
                }

                return $this->httpError(401, 'Incorrect password!');
            }

            return $this->httpError(404, 'Account does not exist!');
        }

        return $this->httpError(400, 'Invalid input!');
    }
}
