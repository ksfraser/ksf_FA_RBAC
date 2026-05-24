<?php
/**
 * FrontAccounting Exception
 *
 * Base exception for FrontAccounting module operations
 *
 * @package Ksfraser\Exceptions\FrontAccounting
 */

declare(strict_types=1);

namespace Ksfraser\Exceptions\FrontAccounting;

class FAException extends \Exception
{
    protected string $moduleName;
    protected array $context;

    public function __construct(string $message, string $moduleName = '', array $context = [], int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->moduleName = $moduleName;
        $this->context = $context;
    }

    public function getModuleName(): string
    {
        return $this->moduleName;
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