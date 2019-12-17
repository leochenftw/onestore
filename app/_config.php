<?php

use SilverStripe\Security\PasswordValidator;
use SilverStripe\Security\Member;
use SilverStripe\Control\Director;
use Leochenftw\Debugger;

$validator = new PasswordValidator();
$validator->minLength(8);
$validator->checkHistoricalPasswords(0);
Member::set_password_validator($validator);

Director::forceSSL();
if (!empty($_SERVER['SERVER_NAME']) && $_SERVER['SERVER_NAME'] == 'one-stop.co.nz') {
    Director::forceWWW();
}
