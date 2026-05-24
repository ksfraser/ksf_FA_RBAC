<?php

namespace Ksfraser\Exceptions\Tests\ProjectManagement;

use PHPUnit\Framework\TestCase;
use Ksfraser\Exceptions\ProjectManagement\ProjectException;
use Ksfraser\Exceptions\ProjectManagement\ProjectNotFoundException;
use Ksfraser\Exceptions\ProjectManagement\TaskNotFoundException;

class ProjectExceptionTest extends TestCase
{
    public function testProjectExceptionBasic(): void
    {
        $exception = new ProjectException('Project error');
        $this->assertEquals('Project error', $exception->getMessage());
    }

    public function testProjectNotFoundException(): void
    {
        $exception = new ProjectNotFoundException('proj-123');
        $this->assertEquals('Project proj-123 not found', $exception->getMessage());
    }

    public function testTaskNotFoundException(): void
    {
        $exception = new TaskNotFoundException('task-456');
        $this->assertEquals('Task task-456 not found', $exception->getMessage());
    }

    public function testExceptionInheritance(): void
    {
        $exception = new ProjectNotFoundException('proj-789');
        $this->assertInstanceOf(ProjectException::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
    }
}