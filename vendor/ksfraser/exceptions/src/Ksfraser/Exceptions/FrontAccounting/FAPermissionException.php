<?php
/**
 * FrontAccounting Permission Exception
 *
 * @package Ksfraser\Exceptions\FrontAccounting
 */

declare(strict_types=1);

namespace Ksfraser\Exceptions\FrontAccounting;

class FAPermissionException extends FAException
{
    private string $userId;
    private string $requiredPermission;

    public function __construct(string $userId, string $requiredPermission, string $moduleName = '', array $context = [], int $code = 0, ?\Throwable $previous = null)
    {
        $message = "User '{$userId}' does not have required permission: {$requiredPermission}";
        parent::__construct($message, $moduleName, $context, $code, $previous);
        $this->userId = $userId;
        $this->requiredPermission = $requiredPermission;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getRequiredPermission(): string
    {
        return $this->requiredPermission;
    }
}