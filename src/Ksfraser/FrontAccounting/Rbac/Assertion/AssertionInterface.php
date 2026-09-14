<?php
declare(strict_types=1);

namespace Ksfraser\FrontAccounting\Rbac\Assertion;

/**
 * AssertionInterface - Contract for dynamic assertions.
 *
 * Dynamic assertions allow record-level access control checks.
 * They are injected at check time and receive the user and resource.
 *
 * Inspired by Zend RBAC assertions.
 *
 * @since 1.0.0
 */
interface AssertionInterface
{
    /**
     * Assert that the user has access to the resource.
     *
     * @param int $userId The user ID
     * @param mixed $resource The resource being accessed
     * @param array $context Additional context (module, action, etc.)
     * @return bool True if access should be granted
     *
     * @since 1.0.0
     */
    public function assert(int $userId, $resource, array $context = []): bool;
}
