<?php
/**
 * CRM Permission Exception
 *
 * @package Ksfraser\Exceptions\CRM
 */

declare(strict_types=1);

namespace Ksfraser\Exceptions\CRM;

class CRMPermissionException extends CRMException
{
    private string $userId;
    private string $requiredPermission;

    public function __construct(string $userId, string $requiredPermission, string $debtorNo = '', array $context = [])
    {
        $message = "User '{$userId}' does not have required permission: {$requiredPermission}";
        parent::__construct($message, $debtorNo, $context);
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