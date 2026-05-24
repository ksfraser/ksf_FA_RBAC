<?php
/**
 * Calendar Event Not Found Exception
 *
 * @package Ksfraser\Exceptions\Calendar
 */

declare(strict_types=1);

namespace Ksfraser\Exceptions\Calendar;

class CalendarEventNotFoundException extends CalendarException
{
    public function __construct(string $eventId)
    {
        parent::__construct("Calendar event not found: {$eventId}");
    }
}