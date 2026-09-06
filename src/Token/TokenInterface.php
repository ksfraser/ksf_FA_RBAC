<?php
declare(strict_types=1);

namespace Ksfraser\FrontAccounting\Rbac\Token;

/**
 * TokenInterface - Represents the current user's authorization context.
 *
 * Inspired by Symfony Security TokenInterface.
 *
 * @since 1.0.0
 */
interface TokenInterface
{
    /**
     * Get the user ID.
     *
     * @return int
     *
     * @since 1.0.0
     */
    public function getUserId(): int;

    /**
     * Get the user's roles.
     *
     * @return array
     *
     * @since 1.0.0
     */
    public function getRoles(): array;

    /**
     * Get the underlying user object.
     *
     * @return mixed|null
     *
     * @since 1.0.0
     */
    public function getUser();

    /**
     * Check if the token is authenticated.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function isAuthenticated(): bool;
}
