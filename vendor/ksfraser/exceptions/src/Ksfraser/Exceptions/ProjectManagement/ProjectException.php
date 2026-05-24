<?php
/**
 * Project Exception
 *
 * Base exception for project management operations
 *
 * @package Ksfraser\Exceptions\ProjectManagement
 */

declare(strict_types=1);

namespace Ksfraser\Exceptions\ProjectManagement;

use Ksfraser\Exceptions\Domain\EntityNotFoundException;

class ProjectException extends \Exception
{
    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}