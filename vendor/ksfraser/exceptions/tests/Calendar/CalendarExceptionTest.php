<?php

namespace Ksfraser\Exceptions\Tests\Calendar;

use PHPUnit\Framework\TestCase;
use Ksfraser\Exceptions\Calendar\CalendarException;
use Ksfraser\Exceptions\Calendar\CalendarEventNotFoundException;
use Ksfraser\Exceptions\Calendar\CalendarValidationException;

class CalendarExceptionTest extends TestCase
{
    public function testCalendarExceptionBasic(): void
    {
        $exception = new CalendarException('Calendar error');
        $this->assertEquals('Calendar error', $exception->getMessage());
    }

    public function testCalendarEventNotFoundException(): void
    {
        $exception = new CalendarEventNotFoundException('evt-123');
        $this->assertEquals('Calendar event not found: evt-123', $exception->getMessage());
    }

    public function testCalendarValidationException(): void
    {
        $errors = ['start_date' => 'Invalid date format', 'end_date' => 'Must be after start'];
        $exception = new CalendarValidationException('Validation failed', $errors);
        $this->assertEquals('Validation failed', $exception->getMessage());
        $this->assertEquals($errors, $exception->getErrors());
    }

    public function testExceptionInheritance(): void
    {
        $exception = new CalendarEventNotFoundException('evt-456');
        $this->assertInstanceOf(CalendarException::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
    }
}