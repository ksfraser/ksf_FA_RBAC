<?php

namespace Ksfraser\FrontAccounting\Rbac\Voter;

use Ksfraser\FrontAccounting\Rbac\Token\TokenInterface;

class SupplierVoter extends AbstractVoter
{
    protected const SUPPORTED_ACTIONS = ['view', 'edit', 'delete', 'approve', 'pay'];
    protected const SUPPORTED_CLASS = null;
    protected const MODULE = 'supplier';

    protected function doVote(string $action, $subject, array $roles, array $context): ?bool
    {
        if (in_array('admin', $roles, true)) {
            return true;
        }

        if (in_array('ap_clerk', $roles, true)) {
            return in_array($action, ['view', 'edit', 'approve', 'pay'], true);
        }

        if (in_array('manager', $roles, true)) {
            return in_array($action, ['view', 'edit', 'delete', 'approve', 'pay'], true);
        }

        if (in_array('clerk', $roles, true)) {
            return $action === 'view';
        }

        return false;
    }
}