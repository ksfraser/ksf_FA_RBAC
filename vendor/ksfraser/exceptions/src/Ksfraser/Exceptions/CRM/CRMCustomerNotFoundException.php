<?php
/**
 * CRM Customer Not Found Exception
 *
 * @package Ksfraser\Exceptions\CRM
 */

declare(strict_types=1);

namespace Ksfraser\Exceptions\CRM;

class CRMCustomerNotFoundException extends CRMException
{
    public function __construct(string $debtorNo, array $context = [])
    {
        parent::__construct("CRM customer not found: {$debtorNo}", $debtorNo, $context);
    }
}