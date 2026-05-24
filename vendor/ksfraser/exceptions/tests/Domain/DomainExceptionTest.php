<?php

namespace Ksfraser\Exceptions\Tests\Domain;

use PHPUnit\Framework\TestCase;
use Ksfraser\Exceptions\Domain\EntityNotFoundException;
use Ksfraser\Exceptions\Domain\InvalidRepositoryStateException;

class DomainExceptionTest extends TestCase
{
    public function testEntityNotFoundWithId(): void
    {
        $exception = EntityNotFoundException::withId('User', 'user-123');
        $this->assertEquals('User not found with id: user-123', $exception->getMessage());
        $this->assertInstanceOf(EntityNotFoundException::class, $exception);
    }

    public function testEntityNotFoundWithCriteria(): void
    {
        $criteria = ['email' => 'test@example.com', 'status' => 'active'];
        $exception = EntityNotFoundException::withCriteria('User', $criteria);
        $this->assertStringContainsString('User not found matching', $exception->getMessage());
        $this->assertStringContainsString('email=', $exception->getMessage());
        $this->assertStringContainsString('status=active', $exception->getMessage());
    }

    public function testEntityNotFoundGeneric(): void
    {
        $exception = EntityNotFoundException::notFound('User with email not found');
        $this->assertEquals('User with email not found', $exception->getMessage());
    }

    public function testInvalidRepositoryStateException(): void
    {
        $exception = new InvalidRepositoryStateException('Cannot commit: transaction not started');
        $this->assertEquals('Cannot commit: transaction not started', $exception->getMessage());
    }
}