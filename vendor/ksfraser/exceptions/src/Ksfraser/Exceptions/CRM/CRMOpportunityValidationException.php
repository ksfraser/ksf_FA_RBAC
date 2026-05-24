<?php
/**
 * CRM Opportunity Validation Exception
 *
 * @package Ksfraser\Exceptions\CRM
 */

declare(strict_types=1);

namespace Ksfraser\Exceptions\CRM;

class CRMOpportunityValidationException extends CRMException
{
    private array $validationErrors;

    public function __construct(string $message, string $debtorNo = '', array $validationErrors = [], array $context = [])
    {
        parent::__construct($message, $debtorNo, $context);
        $this->validationErrors = $validationErrors;
    }

    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }
}