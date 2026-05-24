<?php
/**
 * Task Not Found Exception
 *
 * @package Ksfraser\Exceptions\ProjectManagement
 */

declare(strict_types=1);

namespace Ksfraser\Exceptions\ProjectManagement;

class TaskNotFoundException extends ProjectException
{
    public function __construct(string $taskId)
    {
        parent::__construct("Task {$taskId} not found");
    }
}