<?php

namespace App\Web\GraphQL\Mutation;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use SilverStripe\GraphQL\MutationCreator;
use SilverStripe\GraphQL\OperationResolver;
use SilverStripe\Security\Member;
use App\Web\Member\Operator;

class SuspendOperator extends MutationCreator implements OperationResolver
{
    public function attributes()
    {
        return [
            'name'          =>  'suspendOperator',
            'description'   =>  'Suspends an operator'
        ];
    }

    public function type()
    {
        return $this->manager->getType('member');
    }

    public function args()
    {
        return [
            'id'        =>  ['type' => Type::nonNull(Type::id())]
        ];
    }

    public function resolve($object, array $args, $context, ResolveInfo $info)
    {
        if (!singleton(Member::class)->canEdit($context['currentUser'])) {
            throw new \InvalidArgumentException('Member creation not allowed');
        }

        if ($member =   Operator::get()->byID($args['id'])) {
            $member->Suspended  =   true;
            $member->write();
            return $member;
        }

        throw new \InvalidArgumentException('Operator does not exist');
    }
}
