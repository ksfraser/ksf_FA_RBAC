<?php
/**
 * Project Not Found Exception
 *
 * @package Ksfraser\Exceptions\ProjectManagement
 */

declare(strict_types=1);

namespace Ksfraser\Exceptions\ProjectManagement;

class ProjectNotFoundException extends ProjectException
{
    public function __construct(string $projectId)
    {
        parent::__construct("Project {$projectId} not found");
    }
}