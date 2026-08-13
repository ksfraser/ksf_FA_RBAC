<?php
declare(strict_types=1);

namespace Ksfraser\HRM\Repository;

use Ksfraser\Database\DbManager;

/**
 * Repository for HRM positions.
 * 
 * Handles position-to-role mappings for RBAC integration.
 * 
 * @package Ksfraser\HRM\Repository
 * @since 1.0.0
 */
class PositionRepository
{
    private string $table = 'hrm_positions';

    /**
     * Find position by ID
     */
    public function findById(int $id): ?array
    {
        $row = DbManager::fetchOne(
            "SELECT * FROM {$this->table} WHERE id = ?",
            [$id]
        );

        if (!$row) {
            return null;
        }

        return $this->hydrateFromRow($row);
    }

    /**
     * Find position by name
     */
    public function findByName(string $name): ?array
    {
        $row = DbManager::fetchOne(
            "SELECT * FROM {$this->table} WHERE name = ?",
            [$name]
        );

        if (!$row) {
            return null;
        }

        return $this->hydrateFromRow($row);
    }

    /**
     * Find all positions
     */
    public function findAll(): array
    {
        $rows = DbManager::fetchAll(
            "SELECT * FROM {$this->table} ORDER BY name"
        );

        return array_map([$this, 'hydrateFromRow'], $rows);
    }

    /**
     * Find positions assigned to an employee
     * Returns all past and current positions for the employee
     */
    public function findByEmployeeId(int $employeeId): array
    {
        $sql = "SELECT hrm_positions.*,
                       ep.start_date, ep.end_date,
                       rr.access_role_id
                FROM hrm_positions
                JOIN ksf_hrm_employee_positions ep ON ep.position_id = hrm_positions.id
                LEFT JOIN ksf_hrm_position_roles rr ON rr.position_id = hrm_positions.id
                WHERE ep.employee_id = ?
                ORDER BY ep.start_date DESC";

        $rows = DbManager::fetchAll($sql, [$employeeId]);
        return array_map([$this, 'hydrateFromRow'], $rows);
    }

    /**
     * Find current (active) positions for an employee
     * Positions where end_date is NULL or in the future
     */
    public function findCurrentByEmployeeId(int $employeeId): array
    {
        $now = (new \DateTime())->format('Y-m-d');
        $sql = "SELECT hrm_positions.*,
                       ep.start_date, ep.end_date,
                       rr.access_role_id
                FROM hrm_positions
                JOIN ksf_hrm_employee_positions ep ON ep.position_id = hrm_positions.id
                LEFT JOIN ksf_hrm_position_roles rr ON rr.position_id = hrm_positions.id
                WHERE ep.employee_id = ?
                  AND (ep.end_date IS NULL OR ep.end_date >= ?)
                ORDER BY ep.start_date DESC";

        $rows = DbManager::fetchAll($sql, [$employeeId, $now]);
        return array_map([$this, 'hydrateFromRow'], $rows);
    }

    /**
     * Get the RBAC role ID(s) for a given position
     */
    public function getRbacRoleIdsForPosition(int $positionId): array
    {
        $rows = DbManager::fetchAll(
            "SELECT access_role_id FROM ksf_hrm_position_roles WHERE position_id = ?",
            [$positionId]
        );

        return array_map(function($row) {
            return (int)$row['access_role_id'];
        }, $rows);
    }

    /**
     * Assign RBAC role to a position
     */
    public function assignRoleToPosition(int $positionId, int $roleId): bool
    {
        $sql = "INSERT INTO ksf_hrm_position_roles (position_id, access_role_id)
                VALUES (?, ?)";
        
        return DbManager::execute($sql, [$positionId, $roleId]) > 0;
    }

    /**
     * Remove RBAC role from a position
     */
    public function removeRoleFromPosition(int $positionId, int $roleId): bool
    {
        $sql = "DELETE FROM ksf_hrm_position_roles 
                WHERE position_id = ? AND access_role_id = ?";
        
        return DbManager::execute($sql, [$positionId, $roleId]) > 0;
    }

    private function hydrateFromRow(array $row): array
    {
        return [
            'id' => (int)$row['id'],
            'name' => $row['name'] ?? null,
            'description' => $row['description'] ?? null,
            'department' => $row['department'] ?? null,
            'created_at' => $row['created_at'] ?? null,
        ];
    }
}