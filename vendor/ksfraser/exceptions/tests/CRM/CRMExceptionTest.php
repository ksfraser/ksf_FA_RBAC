<?php

namespace Ksfraser\Exceptions\Tests\CRM;

use PHPUnit\Framework\TestCase;
use Ksfraser\Exceptions\CRM\CRMException;
use Ksfraser\Exceptions\CRM\CRMCustomerNotFoundException;
use Ksfraser\Exceptions\CRM\CRMDatabaseException;

class CRMExceptionTest extends TestCase
{
    public function testCRMExceptionBasic(): void
    {
        $exception = new CRMException('Test message', 'debtor123', ['key' => 'value']);
        $this->assertEquals('Test message', $exception->getMessage());
        $this->assertEquals('debtor123', $exception->getDebtorNo());
        $this->assertEquals(['key' => 'value'], $exception->getContext());
        $this->assertEquals('value', $exception->getContextValue('key'));
        $this->assertNull($exception->getContextValue('nonexistent'));
    }

    public function testCRMCustomerNotFoundException(): void
    {
        $exception = new CRMCustomerNotFoundException('debtor456');
        $this->assertEquals('CRM customer not found: debtor456', $exception->getMessage());
        $this->assertEquals('debtor456', $exception->getDebtorNo());
    }

    public function testCRMDatabaseException(): void
    {
        $exception = new CRMDatabaseException('Query failed', 'SELECT * FROM customers', ['param1'], ['context' => 'test']);
        $this->assertEquals('Query failed', $exception->getMessage());
        $this->assertEquals('SELECT * FROM customers', $exception->getQuery());
        $this->assertEquals(['param1'], $exception->getParameters());
        $this->assertEquals(['context' => 'test'], $exception->getContext());
    }

    public function testCRMExceptionInheritance(): void
    {
        $exception = new CRMCustomerNotFoundException('debtor789');
        $this->assertInstanceOf(CRMException::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
    }

    public function testExceptionChaining(): void
    {
        $previous = new \RuntimeException('Previous error');
        $exception = new CRMException('New error', '', [], 0, $previous);
        $this->assertSame($previous, $exception->getPrevious());
    }
}