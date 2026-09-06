<?php
declare(strict_types=1);

namespace Ksfraser\FrontAccounting\Rbac\Voter;

/**
 * AbstractVoter - Base class for RBAC voters.
 *
 * Provides common functionality for voters including:
 * - Supported actions definition
 * - Subject class/interface checking
 * - Abstain on unsupported combinations
 *
 * @since 1.0.0
 */
abstract class AbstractVoter implements VoterInterface
{
    /**
     * @var array Supported actions for this voter (e.g., ['view', 'edit', 'delete'])
     */
    protected const SUPPORTED_ACTIONS = [];

    /**
     * @var string|null The class/interface this voter supports, or null for any
     */
    protected const SUPPORTED_CLASS = null;

    /**
     * @var string The module this voter belongs to (e.g., 'customer', 'debtor_trans')
     */
    protected const MODULE = '';

    /**
     * Check if this voter supports the given action and subject.
     *
     * @param string $action
     * @param mixed $subject
     * @return bool
     *
     * @since 1.0.0
     */
    public function supports(string $action, $subject): bool
    {
        if (!in_array($action, static::SUPPORTED_ACTIONS, true)) {
            return false;
        }

        $supportedClass = $this->getSupportedClassName();
        if ($supportedClass === null) {
            return true;
        }

        if ($subject === null) {
            return true;
        }

        return is_object($subject) && $subject instanceof $supportedClass;
    }

    /**
     * Get the supported class name.
     *
     * @return string|null
     *
     * @since 1.0.0
     */
    protected function getSupportedClassName(): ?string
    {
        return static::SUPPORTED_CLASS;
    }

    /**
     * Vote on the given action and subject.
     * Default implementation - subclasses should override for specific logic.
     *
     * @param string $action
     * @param mixed $subject
     * @param array $roles
     * @param array $context
     * @return bool|null
     *
     * @since 1.0.0
     */
    public function voteOnAttribute(string $action, $subject, array $roles, array $context = []): ?bool
    {
        if (!$this->supports($action, $subject)) {
            return null;
        }

        return $this->doVote($action, $subject, $roles, $context);
    }

    /**
     * Perform the actual vote.
     * Subclasses must implement this method.
     *
     * @param string $action
     * @param mixed $subject
     * @param array $roles
     * @param array $context
     * @return bool|null
     *
     * @since 1.0.0
     */
    abstract protected function doVote(string $action, $subject, array $roles, array $context): ?bool;

    /**
     * Check if any of the given roles have the required permission.
     *
     * @param array $roles
     * @param string $permission
     * @return bool
     *
     * @since 1.0.0
     */
    protected function hasPermission(array $roles, string $permission): bool
    {
        foreach ($roles as $role) {
            if ($this->roleHasPermission($role, $permission)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if a specific role has the given permission.
     * Can be overridden to check hierarchical roles.
     *
     * @param string $role
     * @param string $permission
     * @return bool
     *
     * @since 1.0.0
     */
    protected function roleHasPermission(string $role, string $permission): bool
    {
        return $role === 'admin' || strpos($permission, $role) !== false;
    }

    /**
     * Get the module this voter belongs to.
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function getModule(): string
    {
        return static::MODULE;
    }
}
