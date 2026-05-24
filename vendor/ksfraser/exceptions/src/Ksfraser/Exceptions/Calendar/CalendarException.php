<?php
/**
 * Calendar Exception
 *
 * Base exception for calendar operations
 *
 * @package Ksfraser\Exceptions\Calendar
 */

declare(strict_types=1);

namespace Ksfraser\Exceptions\Calendar;

class CalendarException extends \Exception
{
    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}