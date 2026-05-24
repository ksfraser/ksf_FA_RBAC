<?php
/**
 * FrontAccounting Database Exception
 *
 * @package Ksfraser\Exceptions\FrontAccounting
 */

declare(strict_types=1);

namespace Ksfraser\Exceptions\FrontAccounting;

class FADatabaseException extends FAException
{
    private string $query;
    private array $parameters;

    public function __construct(string $message, string $query = '', array $parameters = [], string $moduleName = '', array $context = [], int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $moduleName, $context, $code, $previous);
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