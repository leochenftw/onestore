<?php

namespace App\Web\Extension;

use SilverStripe\Security\Authenticator;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use SilverStripe\Security\IdentityStore;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\DataExtension;
use SilverStripe\GraphQL\Scaffolding\Interfaces\ScaffoldingProvider;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\SchemaScaffolder;

class MemberExension extends DataExtension implements ScaffoldingProvider
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

    /**
     * Event handler called before writing to the database.
     */
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        if (!empty($this->owner->FirstName)) {
            $this->owner->FirstName =   ucfirst($this->owner->FirstName);
        }

        if (!empty($this->owner->Surname)) {
            $this->owner->Surname   =   ucfirst($this->owner->Surname);
        }
    }

    public function provideGraphQLScaffolding(SchemaScaffolder $scaffolder)
    {
        // $scaffolder->mutation('login', Member::class)
        //   ->addArgs(['Email' => 'String!', 'Password' => 'String!'])
        //   ->setResolver(function($obj, $args, $context) {
        //     /** @var Security $security */
        //     $security = Injector::inst()->get(Security::class);
        //     $authenticators = $security->getApplicableAuthenticators(Authenticator::LOGIN);
        //     $request = Controller::curr()->getRequest();
        //     $member = null;
        //     $result = null;
        //     if (count($authenticators)) {
        //       /** @var Authenticator $authenticator */
        //       foreach ($authenticators as $authenticator) {
        //         $member = $authenticator->authenticate($args, $request, $result);
        //         if ($result->isValid()) {
        //           break;
        //         }
        //       }
        //     }
        //     if ($member) {
        //       Injector::inst()->get(IdentityStore::class)->logIn($member);
        //     }
        //     return $member;
        //   });
        //
        //   $scaffolder
        //       ->type(Member::class)
        //           ->addFields(['ID', 'Title', 'Email'])
        //           ->operation(SchemaScaffolder::READ)
        //               ->end()
        //           ->operation(SchemaScaffolder::UPDATE)
        //               ->end()
        //           ->end();

        return $scaffolder;
    }
}
