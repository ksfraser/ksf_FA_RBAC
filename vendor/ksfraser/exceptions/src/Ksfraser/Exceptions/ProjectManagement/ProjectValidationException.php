<?php
/**
 * Project Validation Exception
 *
 * @package Ksfraser\Exceptions\ProjectManagement
 */

declare(strict_types=1);

namespace Ksfraser\Exceptions\ProjectManagement;

use Ksfraser\Exceptions\Utility\ValidationException;

class ProjectValidationException extends \Exception
{
    private array $errors;

    public function __construct(string $message, array $errors = [], int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}