<?php
/**
 * CRM Customer Already Exists Exception
 *
 * @package Ksfraser\Exceptions\CRM
 */

declare(strict_types=1);

namespace Ksfraser\Exceptions\CRM;

class CRMCustomerAlreadyExistsException extends CRMException
{
    public function __construct(string $debtorNo, array $context = [])
    {
        parent::__construct("CRM customer already exists: {$debtorNo}", $debtorNo, $context);
    }
}