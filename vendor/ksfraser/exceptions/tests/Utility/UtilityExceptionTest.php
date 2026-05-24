<?php

namespace Ksfraser\Exceptions\Tests\Utility;

use PHPUnit\Framework\TestCase;
use Ksfraser\Exceptions\Utility\ValidationException;
use Ksfraser\Exceptions\Utility\FileNotFoundException;
use Ksfraser\Exceptions\Utility\ParsingFailedException;

class UtilityExceptionTest extends TestCase
{
    public function testValidationException(): void
    {
        $errors = ['field1' => 'Required', 'field2' => 'Invalid format'];
        $exception = new ValidationException($errors, 'Validation failed');
        $this->assertEquals('Validation failed', $exception->getMessage());
        $this->assertEquals(2, count($exception->getErrors()));
        $this->assertContains('Required', $exception->getErrors());
        $this->assertContains('Invalid format', $exception->getErrors());
    }

    public function testValidationExceptionMissingFields(): void
    {
        $exception = ValidationException::missingFields(['email', 'name']);
        $this->assertStringContainsString('Missing required fields', $exception->getMessage());
        $this->assertEquals(2, $exception->getErrorCount());
    }

    public function testFileNotFoundException(): void
    {
        $exception = FileNotFoundException::create('/path/to/file.txt');
        $this->assertEquals('File not found: /path/to/file.txt', $exception->getMessage());
        $this->assertEquals('/path/to/file.txt', $exception->getFilePath());
    }

    public function testParsingFailedException(): void
    {
        $exception = ParsingFailedException::create('Invalid syntax', 42);
        $this->assertStringContainsString('Invalid syntax', $exception->getMessage());
        $this->assertEquals(42, $exception->getLineNumber());
    }

    public function testEncodingMismatchException(): void
    {
        $exception = \Ksfraser\Exceptions\Utility\EncodingMismatchException::create('UTF-8', 'ISO-8859-1');
        $this->assertStringContainsString('UTF-8', $exception->getMessage());
        $this->assertStringContainsString('ISO-8859-1', $exception->getMessage());
        $this->assertEquals('UTF-8', $exception->getDetectedEncoding());
        $this->assertEquals('ISO-8859-1', $exception->getExpectedEncoding());
    }
}