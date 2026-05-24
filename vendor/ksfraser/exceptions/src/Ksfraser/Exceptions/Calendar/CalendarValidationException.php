<?php
/**
 * Calendar Validation Exception
 *
 * @package Ksfraser\Exceptions\Calendar
 */

declare(strict_types=1);

namespace Ksfraser\Exceptions\Calendar;

class CalendarValidationException extends CalendarException
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