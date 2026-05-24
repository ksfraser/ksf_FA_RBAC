<?php
/**
 * CRM Exception
 *
 * Base exception for CRM module operations
 *
 * @package Ksfraser\Exceptions\CRM
 */

declare(strict_types=1);

namespace Ksfraser\Exceptions\CRM;

class CRMException extends \Exception
{
    protected string $debtorNo;
    protected array $context;

    public function __construct(string $message, string $debtorNo = '', array $context = [], int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->debtorNo = $debtorNo;
        $this->context = $context;
    }

    public function getDebtorNo(): string
    {
        return $this->debtorNo;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function getContextValue(string $key)
    {
        return $this->context[$key] ?? null;
    }
}