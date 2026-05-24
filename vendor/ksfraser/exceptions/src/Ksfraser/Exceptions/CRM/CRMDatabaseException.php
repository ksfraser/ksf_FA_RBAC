<?php
/**
 * CRM Database Exception
 *
 * @package Ksfraser\Exceptions\CRM
 */

declare(strict_types=1);

namespace Ksfraser\Exceptions\CRM;

class CRMDatabaseException extends CRMException
{
    private string $query;
    private array $parameters;

    public function __construct(string $message, string $query = '', array $parameters = [], array $context = [], int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, '', $context, $code, $previous);
        $this->query = $query;
        $this->parameters = $parameters;
    }

    public function getQuery(): string
    {
        return $this->query;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }
}