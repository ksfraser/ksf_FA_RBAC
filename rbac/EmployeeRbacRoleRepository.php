<?php
declare(strict_types=1);

namespace Ksfraser\RBAC\Repository;

use Ksfraser\Database\DbManager;

/**
 * Repository for RBAC Employee Roles
 * 
 * Manages direct RBAC role assignments to employees, overriding position and role
 * inherited permissions.
 * 
 * @package Ksfraser\RBAC\Repository
 * @since 1.0.0
 */
class EmployeeRbacRoleRepository
{
    private string $table = '0_ksf_rbac_employee_roles';
    private DbManager $db;

    public function __construct(DbManager $db)
    {
        $this->db = $db;
    }

    /**
     * Get direct RBAC role assigned to an employee
     * 
     * @param int $employeeId
     * @return ?int
     */
    public function findByEmployee(int $employeeId): ?int
    {
        $row = $this->db->fetchOne(
            "SELECT role_id FROM {$this->table} WHERE employee_id = ?",
            [$employeeId]
        );

        return $row['role_id'] ?? null;
    }

    /**
     * Assign RBAC role to an employee
     * 
     * @param int $employeeId
     * @param int $roleId
     * @return bool
     */
    public function assignRoleToEmployee(int $employeeId, int $roleId): bool
    {
        $sql = "INSERT INTO {$this->table} (employee_id, role_id) VALUES (?, ?)";
        
        return $this->db->execute($sql, [$employeeId, $roleId]) > 0;
    }

    /**
     * Remove RBAC role from an employee
     * 
     * @param int $employeeId
     * @return bool
     */
    public function revokeRoleFromEmployee(int $employeeId): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE employee_id = ?";
        
        return $this->db->execute($sql, [$employeeId]) > 0;
    }
}