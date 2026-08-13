<?php
declare(strict_types=1);

namespace Ksfraser\RBAC\Repository;

use Ksfraser\Database\DbManager;

/**
 * Repository for managing RBAC roles and their associations.
 * 
 * @package Ksfraser\RBAC\Repository
 * @since 1.0.0
 */
class RoleRepository
{
    private string $table = '0_ksf_rbac_roles';
    private DbManager $db;

    public function __construct(DbManager $db)
    {
        $this->db = $db;
    }

    /**
     * Find all roles for a given module and permission type
     */
    public function findByPermission(string $module, string $permission): array
    {
        $rows = $this->db->fetchAll(
            "SELECT id, scope, record_type, record_id
             FROM {$this->table}
             WHERE module = ? AND permission = ?",
            [$module, $permission]
        );

        return array_map([$this, 'hydrateFromRow'], $rows);
    }

    /**
     * Find RBAC role by ID
     */
    public function findById(int $id): ?array
    {
        $row = $this->db->fetchOne(
            "SELECT * FROM {$this->table} WHERE id = ?",
            [$id]
        );

        if (!$row) {
            return null;
        }

        return $this->hydrateFromRow($row);
    }

    /**
     * Store HMI access capabilities for HRM roles/link mappings
     */
    public function associateRoleWithPosition(
        int $positionId, 
        int $roleId
    ): bool {
        $sql = "INSERT INTO ksf_hrm_position_roles (position_id, access_role_id)
                VALUES (?, ?)";
        
        return $this->db->execute($sql, [$positionId, $roleId]) > 0;
    }

    /**
     * Get all role records for merge logic
     */
    public function findAllRoles(): array
    {
        $rows = $this->db->fetchAll("SELECT * FROM {$this->table}");
        return array_map([$this, 'hydrateFromRow'], $rows);
    }

    /**
     * Get roles for module display in the grid
     */
    public function getAccessMatrix(string $module): array
    {
        $permissions = ['View', 'Create', 'Edit', 'Delete'];
        $scopes = ['None', 'Mine', 'Team', 'All'];
        $rolePermissions = [];

        foreach ($permissions as $perm) {
            $roles = $this->findByPermission($module, $perm);
            foreach ($scopes as $scope) {
                $key = "{$perm}_{$scope}";
                $rolePermissions[$key] = array_filter($roles, fn($r) => $r['scope'] === $scope);
            }
        }

        return $rolePermissions;
    }

    private function hydrateFromRow(array $row): array
    {
        return [
            'id' => (int)$row['id'],
            'module' => $row['module'] ?? null,
            'permission' => $row['permission'] ?? null,
            'scope' => $row['scope'] ?? null,
            'record_type' => $row['record_type'] ?? null,
            'record_id' => $row['record_id'] ?? null,
            'created_at' => $row['created_at'] ?? null,
        ];
    }
}