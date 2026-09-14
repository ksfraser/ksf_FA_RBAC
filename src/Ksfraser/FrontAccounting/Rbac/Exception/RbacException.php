<?php
declare(strict_types=1);

namespace Ksfraser\FrontAccounting\Rbac\Exception;

/**
 * RbacException - Exception for RBAC errors.
 *
 * @since 1.0.0
 */
class RbacException extends \Exception
{
    /**
     * Create an exception for missing role.
     *
     * @param string $role
     * @return static
     *
     * @since 1.0.0
     */
    public static function roleNotFound(string $role): self
    {
        return new self("Role not found: {$role}");
    }

    /**
     * Create an exception for missing permission.
     *
     * @param string $permission
     * @return static
     *
     * @since 1.0.0
     */
    public static function permissionNotFound(string $permission): self
    {
        return new self("Permission not found: {$permission}");
    }

    /**
     * Create an exception for unauthorized access.
     *
     * @param int $userId
     * @param string $action
     * @param mixed $subject
     * @return static
     *
     * @since 1.0.0
     */
    public static function unauthorized(int $userId, string $action, $subject): self
    {
        $subjectType = is_object($subject) ? get_class($subject) : gettype($subject);
        return new self("User {$userId} is not authorized to {$action} {$subjectType}");
    }
}
