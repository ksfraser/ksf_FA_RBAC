<?php

namespace Ksfraser\FrontAccounting\Rbac\Voter;

use Ksfraser\FrontAccounting\Rbac\Token\TokenInterface;

class DebtorVoter extends AbstractVoter
{
    protected const SUPPORTED_ACTIONS = ['view', 'edit', 'delete', 'approve', 'void'];
    protected const SUPPORTED_CLASS = null;
    protected const MODULE = 'debtor_trans';

    protected function doVote(string $action, $subject, array $roles, array $context): ?bool
    {
        if (in_array('admin', $roles, true)) {
            return true;
        }

        if (in_array('ar_clerk', $roles, true)) {
            return in_array($action, ['view', 'edit', 'approve'], true);
        }

        if (in_array('manager', $roles, true)) {
            return in_array($action, ['view', 'edit', 'approve', 'void'], true);
        }

        if (in_array('clerk', $roles, true)) {
            return $action === 'view';
        }

        if (in_array('salesman', $roles, true)) {
            if ($action === 'view' && isset($context['salesman_code'])) {
                return $this->isOwnSalesman($context);
            }
            return false;
        }

        return false;
    }

    private function isOwnSalesman(array $context): bool
    {
        return isset($context['user_salesman']) &&
               $context['user_salesman'] === $context['salesman_code'];
    }
}