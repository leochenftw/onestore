<?php

namespace App\Web\GraphQL\Mutation;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use SilverStripe\GraphQL\MutationCreator;
use SilverStripe\GraphQL\OperationResolver;
use SilverStripe\Security\Member;
use App\Web\Member\Operator;

class CreateOperatorMutationCreator extends MutationCreator implements OperationResolver
{
    public function attributes()
    {
        return [
            'name'          =>  'createOperator',
            'description'   =>  'Creates an operator'
        ];
    }

    public function type()
    {
        return $this->manager->getType('member');
    }

    public function args()
    {
        return [
            'email'     =>  ['type' => Type::nonNull(Type::string())],
            'firstname' =>  ['type' => Type::nonNull(Type::string())],
            'surname'   =>  ['type' => Type::nonNull(Type::string())],
            'password'  =>  ['type' => Type::nonNull(Type::string())],
            'repass'    =>  ['type' => Type::nonNull(Type::string())]
        ];
    }

    public function resolve($object, array $args, $context, ResolveInfo $info)
    {
        if (!singleton(Member::class)->canCreate($context['currentUser'])) {
            throw new \InvalidArgumentException('Member creation not allowed');
        }

        if ($args['password'] != $args['repass']) {
            throw new \InvalidArgumentException('Passwords don\'t match');
        }

        $member =   Operator::get()->filter(['Email' => $args['email']])->first();

        if (empty($member)) {
            $member =   Operator::create();
        } else {
            $member->Suspended  =   false;
        }

        $member->FirstName  =   $args['firstname'];
        $member->Surname    =   $args['surname'];
        $member->Password   =   $args['password'];
        $member->Email      =   $args['email'];
        $member->write();

        return $member;
    }
}
