<?php
/**
 * Calendar Import Exception
 *
 * Exception for iCal import/export errors
 *
 * @package Ksfraser\Exceptions\Calendar
 */

declare(strict_types=1);

namespace Ksfraser\Exceptions\Calendar;

use Ksfraser\Exceptions\Utility\ParsingFailedException;

class CalendarImportException extends CalendarException
{
    private string $importSource;

    public function __construct(string $message, string $importSource = '', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->importSource = $importSource;
    }

    public function getImportSource(): string
    {
        return $this->importSource;
    }
}