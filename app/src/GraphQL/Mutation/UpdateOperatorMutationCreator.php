<?php

namespace App\Web\GraphQL\Mutation;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use SilverStripe\GraphQL\MutationCreator;
use SilverStripe\GraphQL\OperationResolver;
use SilverStripe\Security\Member;
use App\Web\Member\Operator;

class UpdateOperatorMutationCreator extends MutationCreator implements OperationResolver
{
    public function attributes()
    {
        return [
            'name'          =>  'updateOperator',
            'description'   =>  'Updates an operator'
        ];
    }

    public function type()
    {
        return $this->manager->getType('member');
    }

    public function args()
    {
        return [
            'id'        =>  ['type' => Type::nonNull(Type::id())],
            'email'     =>  ['type' => Type::nonNull(Type::string())],
            'firstname' =>  ['type' => Type::nonNull(Type::string())],
            'surname'   =>  ['type' => Type::nonNull(Type::string())],
            'password'  =>  ['type' => Type::string()],
            'repass'    =>  ['type' => Type::string()]
        ];
    }

    public function resolve($object, array $args, $context, ResolveInfo $info)
    {
        if (!singleton(Member::class)->canEdit($context['currentUser'])) {
            throw new \InvalidArgumentException('Member creation not allowed');
        }

        if (!empty($args['password']) && ($args['password'] != $args['repass'])) {
            throw new \InvalidArgumentException('Passwords don\'t match');
        }

        if ($member =   Operator::get()->byID($args['id'])) {
            $member->FirstName  =   $args['firstname'];
            $member->Surname    =   $args['surname'];
            $member->Email      =   $args['email'];
            if (!empty($args['password'])) {
                $member->Password   =   $args['password'];
            }
            $member->write();
            return $member;
        }

        throw new \InvalidArgumentException('Operator does not exist');
    }
}
