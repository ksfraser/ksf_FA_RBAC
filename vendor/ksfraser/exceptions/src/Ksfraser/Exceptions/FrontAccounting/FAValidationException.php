<?php
/**
 * FrontAccounting Validation Exception
 *
 * @package Ksfraser\Exceptions\FrontAccounting
 */

declare(strict_types=1);

namespace Ksfraser\Exceptions\FrontAccounting;

class FAValidationException extends FAException
{
    private array $validationErrors;
    private array $fieldName;

    public function __construct(string $message, array $validationErrors = [], string $fieldName = '', string $moduleName = '', array $context = [], int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $moduleName, $context, $code, $previous);
        $this->validationErrors = $validationErrors;
        $this->fieldName = $fieldName;
    }

    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }

    public function getFieldName(): string
    {
        return $this->fieldName;
    }
}