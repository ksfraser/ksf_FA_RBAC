<?php
declare(strict_types=1);

namespace Ksfraser\FrontAccounting\Rbac\Token;

/**
 * FaUserToken - FA-specific implementation of TokenInterface.
 *
 * Wraps the FA $_SESSION["wa_current_user"] object.
 *
 * @since 1.0.0
 */
class FaUserToken implements TokenInterface
{
    /** @var object|null */
    private $user;

    /** @var int */
    private $userId;

    /** @var array */
    private $roles;

    /**
     * Constructor.
     *
     * @param object|null $faUser The FA current_user object
     *
     * @since 1.0.0
     */
    public function __construct($faUser = null)
    {
        $this->user = $faUser;

        if ($faUser !== null && isset($faUser->user)) {
            $this->userId = (int) $faUser->user;
            $this->roles = $this->extractRoles($faUser);
        } else {
            $this->userId = 0;
            $this->roles = [];
        }
    }

    /**
     * Extract roles from the FA user object.
     *
     * @param object $faUser
     * @return array
     *
     * @since 1.0.0
     */
    private function extractRoles($faUser): array
    {
        $roles = [];

        if (isset($faUser->access) && is_array($faUser->access)) {
            foreach ($faUser->access as $area => $level) {
                if ($level > 0) {
                    $roles[] = $this->accessLevelToRole($area, $level);
                }
            }
        }

        if (isset($faUser->salesman) && !empty($faUser->salesman)) {
            $roles[] = 'salesman';
        }

        if (isset($faUser->user) && $faUser->user == 1) {
            $roles[] = 'admin';
        }

        return array_unique($roles);
    }

    /**
     * Convert FA access level to a role name.
     *
     * @param string $area
     * @param int $level
     * @return string
     *
     * @since 1.0.0
     */
    private function accessLevelToRole(string $area, int $level): string
    {
        if ($level >= 99) {
            return 'admin';
        }

        if ($level >= 10) {
            return 'manager';
        }

        if ($level >= 1) {
            return 'clerk';
        }

        return 'viewer';
    }

    /**
     * {@inheritdoc}
     */
    public function getUserId(): int
    {
        return $this->userId;
    }

    /**
     * {@inheritdoc}
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     * {@inheritdoc}
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * {@inheritdoc}
     */
    public function isAuthenticated(): bool
    {
        return $this->userId > 0;
    }

    /**
     * Create a token from the current FA session.
     *
     * @return static
     *
     * @since 1.0.0
     */
    public static function fromSession(): self
    {
        global $db_connections;

        if (!isset($_SESSION["wa_current_user"])) {
            return new static(null);
        }

        return new static($_SESSION["wa_current_user"]);
    }

    /**
     * Check if user has a specific role.
     *
     * @param string $role
     * @return bool
     *
     * @since 1.0.0
     */
    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles, true);
    }

    /**
     * Check if user has any of the given roles.
     *
     * @param array $roles
     * @return bool
     *
     * @since 1.0.0
     */
    public function hasAnyRole(array $roles): bool
    {
        return !empty(array_intersect($this->roles, $roles));
    }
}
