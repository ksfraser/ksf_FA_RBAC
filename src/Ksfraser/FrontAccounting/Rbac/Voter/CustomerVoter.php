<?php

namespace Ksfraser\FrontAccounting\Rbac\Voter;

use Ksfraser\FrontAccounting\Rbac\Token\TokenInterface;

class CustomerVoter extends AbstractVoter
{
    protected const SUPPORTED_ACTIONS = ['view', 'edit', 'delete', 'create', 'approve'];
    protected const SUPPORTED_CLASS = null;
    protected const MODULE = 'customer';

    protected function doVote(string $action, $subject, array $roles, array $context): ?bool
    {
        if (in_array('admin', $roles, true)) {
            return true;
        }

        if (in_array('manager', $roles, true)) {
            return in_array($action, ['view', 'edit', 'create'], true);
        }

        if (in_array('clerk', $roles, true)) {
            return in_array($action, ['view', 'create'], true);
        }

        if (in_array('salesman', $roles, true)) {
            if ($action === 'view' && isset($context['owner_id'])) {
                return $this->isOwner($context);
            }
            return $action === 'view';
        }

        if (in_array('viewer', $roles, true)) {
            return $action === 'view';
        }

        return false;
    }

    private function isOwner(array $context): bool
    {
        if (!isset($context['user_id'], $context['owner_id'])) {
            return false;
        }
        return (int) $context['user_id'] === (int) $context['owner_id'];
    }
}