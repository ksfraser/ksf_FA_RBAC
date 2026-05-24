<?php

namespace Ksfraser\Exceptions\Tests\FrontAccounting;

use PHPUnit\Framework\TestCase;
use Ksfraser\Exceptions\FrontAccounting\FAException;
use Ksfraser\Exceptions\FrontAccounting\FADatabaseException;
use Ksfraser\Exceptions\FrontAccounting\FAEntityNotFoundException;

class FAExceptionTest extends TestCase
{
    public function testFAExceptionBasic(): void
    {
        $exception = new FAException('FA error', 'CRM', ['key' => 'value']);
        $this->assertEquals('FA error', $exception->getMessage());
        $this->assertEquals('CRM', $exception->getModuleName());
        $this->assertEquals(['key' => 'value'], $exception->getContext());
    }

    public function testFADatabaseException(): void
    {
        $exception = new FADatabaseException('Query failed', 'SELECT *', ['param'], 'HRM', ['context' => 'test']);
        $this->assertEquals('Query failed', $exception->getMessage());
        $this->assertEquals('SELECT *', $exception->getQuery());
        $this->assertEquals(['param'], $exception->getParameters());
        $this->assertEquals('HRM', $exception->getModuleName());
    }

    public function testFAEntityNotFoundException(): void
    {
        $exception = new FAEntityNotFoundException('Customer', 'cust-123', 'CRM');
        $this->assertEquals('Customer not found with id: cust-123', $exception->getMessage());
        $this->assertEquals('Customer', $exception->getEntityType());
        $this->assertEquals('cust-123', $exception->getEntityId());
    }

    public function testExceptionInheritance(): void
    {
        $exception = new FADatabaseException('Error', '', [], 'Sales');
        $this->assertInstanceOf(FAException::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
    }
}