<?php
declare(strict_types=1);

namespace Ksfraser\FrontAccounting\Rbac\Storage;

/**
 * PermissionStorageInterface - Contract for permission persistence.
 *
 * @since 1.0.0
 */
interface PermissionStorageInterface
{
    /**
     * Find a permission by name.
     *
     * @param string $name
     * @return array|null
     *
     * @since 1.0.0
     */
    public function findByName(string $name): ?array;

    /**
     * Get all permissions.
     *
     * @return array
     *
     * @since 1.0.0
     */
    public function findAll(): array;

    /**
     * Get roles that have a permission.
     *
     * @param string $permission
     * @return array
     *
     * @since 1.0.0
     */
    public function getRolesWithPermission(string $permission): array;

    /**
     * Save a permission.
     *
     * @param string $name
     * @param array $roles
     * @return void
     *
     * @since 1.0.0
     */
    public function save(string $name, array $roles = []): void;

    /**
     * Delete a permission.
     *
     * @param string $name
     * @return void
     *
     * @since 1.0.0
     */
    public function delete(string $name): void;
}
