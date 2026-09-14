<?php

namespace Ksfraser\FrontAccounting\Rbac\Voter;

use Ksfraser\FrontAccounting\Rbac\Token\TokenInterface;

class StockVoter extends AbstractVoter
{
    protected const SUPPORTED_ACTIONS = ['view', 'edit', 'delete', 'approve', 'transfer'];
    protected const SUPPORTED_CLASS = null;
    protected const MODULE = 'stock';

    protected function doVote(string $action, $subject, array $roles, array $context): ?bool
    {
        if (in_array('admin', $roles, true)) {
            return true;
        }

        if (in_array('warehouse', $roles, true)) {
            return in_array($action, ['view', 'edit', 'transfer'], true);
        }

        if (in_array('manager', $roles, true)) {
            return true;
        }

        if (in_array('clerk', $roles, true)) {
            return in_array($action, ['view', 'edit'], true);
        }

        if (in_array('viewer', $roles, true)) {
            return $action === 'view';
        }

        return false;
    }
}