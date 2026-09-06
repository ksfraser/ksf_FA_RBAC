<?php
declare(strict_types=1);

namespace Ksfraser\FrontAccounting\Rbac\Storage;

/**
 * RoleStorageInterface - Contract for role persistence.
 *
 * @since 1.0.0
 */
interface RoleStorageInterface
{
    /**
     * Find a role by name.
     *
     * @param string $name
     * @return array|null
     *
     * @since 1.0.0
     */
    public function findByName(string $name): ?array;

    /**
     * Get all roles.
     *
     * @return array
     *
     * @since 1.0.0
     */
    public function findAll(): array;

    /**
     * Get parent roles for a role.
     *
     * @param string $name
     * @return array
     *
     * @since 1.0.0
     */
    public function getParents(string $name): array;

    /**
     * Save a role.
     *
     * @param string $name
     * @param array $parents
     * @return void
     *
     * @since 1.0.0
     */
    public function save(string $name, array $parents = []): void;

    /**
     * Delete a role.
     *
     * @param string $name
     * @return void
     *
     * @since 1.0.0
     */
    public function delete(string $name): void;
}
