<?php
declare(strict_types=1);

namespace Ksfraser\FrontAccounting\Rbac;

use Ksfraser\FrontAccounting\Rbac\Token\TokenInterface;
use Ksfraser\FrontAccounting\Rbac\Voter\VoterInterface;
use Laminas\Permissions\Rbac\Rbac;
use Laminas\Permissions\Rbac\Role;
use Laminas\Permissions\Rbac\Assertion\AssertionInterface as ZendAssertionInterface;

/**
 * RbacService - Main RBAC authorization service.
 *
 * Wraps Zend RBAC and provides:
 * - Role/permission management
 * - Voter-based authorization
 * - Decision strategies
 * - Dynamic assertions
 *
 * @since 1.0.0
 */
class RbacService
{
    /** Decision strategy constants */
    public const STRATEGY_AFFIRMATIVE = 'affirmative';
    public const STRATEGY_CONSENSUS = 'consensus';
    public const STRATEGY_UNANIMOUS = 'unanimous';
    public const STRATEGY_PRIORITY = 'priority';

    /** @var Rbac */
    private $rbac;

    /** @var array */
    private $voters = [];

    /** @var string */
    private $strategy = self::STRATEGY_AFFIRMATIVE;

    /** @var array */
    private $moduleAcl = [];

    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->rbac = new Rbac();
        $this->initializeDefaultRoles();
    }

    /**
     * Initialize default roles from design doc.
     *
     * @since 1.0.0
     */
    private function initializeDefaultRoles(): void
    {
        $roleHierarchy = [
            'admin' => [],
            'manager' => ['salesman'],
            'salesman' => ['clerk'],
            'clerk' => [],
            'ar_clerk' => ['clerk'],
            'ap_clerk' => ['clerk'],
            'warehouse' => ['clerk'],
            'viewer' => [],
        ];

        foreach ($roleHierarchy as $role => $parents) {
            $this->addRole($role, $parents);
        }
    }

    /**
     * Add a role to the RBAC system.
     *
     * @param string $name
     * @param array $parents
     * @return void
     *
     * @since 1.0.0
     */
    public function addRole(string $name, array $parents = []): void
    {
        if ($this->rbac->hasRole($name)) {
            $role = $this->rbac->getRole($name);
        } else {
            $role = new Role($name);
            $this->rbac->addRole($role);
        }

        foreach ($parents as $parent) {
            if ($this->rbac->hasRole($parent)) {
                $role->addParent($this->rbac->getRole($parent));
            }
        }
    }

    /**
     * Add a permission to a role.
     *
     * @param string $permission
     * @param array $roles
     * @return void
     *
     * @since 1.0.0
     */
    public function addPermission(string $permission, array $roles = []): void
    {
        foreach ($roles as $roleName) {
            if ($this->rbac->hasRole($roleName)) {
                $role = $this->rbac->getRole($roleName);
                if (!$role->hasPermission($permission)) {
                    $role->addPermission($permission);
                }
            }
        }
    }

    /**
     * Check if a role has a specific permission.
     *
     * @param string $role
     * @param string $permission
     * @return bool
     *
     * @since 1.0.0
     */
    public function isGranted(string $role, string $permission): bool
    {
        if (!$this->rbac->hasRole($role)) {
            return false;
        }

        return $this->rbac->isGranted($role, $permission);
    }

    /**
     * Get all roles.
     *
     * @return array
     *
     * @since 1.0.0
     */
    public function getRoles(): array
    {
        $roles = [];
        foreach ($this->rbac->getRoles() as $role) {
            $roles[] = $role->getName();
        }
        return $roles;
    }

    /**
     * Register a voter.
     *
     * @param VoterInterface $voter
     * @return void
     *
     * @since 1.0.0
     */
    public function registerVoter(VoterInterface $voter): void
    {
        $this->voters[] = $voter;
    }

    /**
     * Set the decision strategy.
     *
     * @param string $strategy
     * @return void
     *
     * @since 1.0.0
     */
    public function setDecisionStrategy(string $strategy): void
    {
        $valid = [
            self::STRATEGY_AFFIRMATIVE,
            self::STRATEGY_CONSENSUS,
            self::STRATEGY_UNANIMOUS,
            self::STRATEGY_PRIORITY,
        ];

        if (in_array($strategy, $valid, true)) {
            $this->strategy = $strategy;
        }
    }

    /**
     * Authorize an action using voters.
     *
     * @param string $action
     * @param mixed $subject
     * @param TokenInterface $token
     * @param array $context
     * @return bool|null
     *
     * @since 1.0.0
     */
    public function authorize(string $action, $subject, TokenInterface $token, array $context = []): ?bool
    {
        $votes = [];
        $abstains = [];

        foreach ($this->voters as $voter) {
            if ($voter->supports($action, $subject)) {
                $vote = $voter->voteOnAttribute($action, $subject, $token->getRoles(), $context);

                if ($vote === null) {
                    $abstains[] = $voter;
                } else {
                    $votes[] = $vote;
                }
            }
        }

        if (empty($votes) && empty($abstains)) {
            return null;
        }

        return $this->tallyVotes($votes, count($abstains));
    }

    /**
     * Tally votes using the configured strategy.
     *
     * @param array $votes
     * @param int $abstainCount
     * @return bool|null
     *
     * @since 1.0.0
     */
    private function tallyVotes(array $votes, int $abstainCount): ?bool
    {
        if (empty($votes) && $abstainCount > 0) {
            return null;
        }

        if (empty($votes)) {
            return false;
        }

        switch ($this->strategy) {
            case self::STRATEGY_AFFIRMATIVE:
                return in_array(true, $votes, true);

            case self::STRATEGY_CONSENSUS:
                $yes = array_sum($votes);
                $no = count($votes) - $yes;
                return $yes > $no;

            case self::STRATEGY_UNANIMOUS:
                return count($votes) > 0 && !in_array(false, $votes, true);

            case self::STRATEGY_PRIORITY:
                return $votes[0];

            default:
                return in_array(true, $votes, true);
        }
    }

    /**
     * Register a module's ACL.
     *
     * @param string $module
     * @param array $permissions
     * @return void
     *
     * @since 1.0.0
     */
    public function registerModuleAcl(string $module, array $permissions): void
    {
        $this->moduleAcl[$module] = $permissions;
    }

    /**
     * Get the ACL for a module.
     *
     * @param string $module
     * @return array
     *
     * @since 1.0.0
     */
    public function getModuleAcl(string $module): array
    {
        return $this->moduleAcl[$module] ?? [];
    }

    /**
     * Check if a role can perform an action on a module.
     *
     * @param string $role
     * @param string $module
     * @param string $action
     * @return bool
     *
     * @since 1.0.0
     */
    public function canAccess(string $role, string $module, string $action): bool
    {
        $acl = $this->getModuleAcl($module);

        if (!isset($acl[$action])) {
            return false;
        }

        return in_array($role, $acl[$action], true);
    }

    /**
     * Get the underlying Zend RBAC instance.
     *
     * @return Rbac
     *
     * @since 1.0.0
     */
    public function getRbac(): Rbac
    {
        return $this->rbac;
    }
}
