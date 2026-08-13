<?php
declare(strict_types=1);

namespace Ksfraser\HRM\Repository;

use Ksfraser\HRM\Entity\Employee;
use Ksfraser\Database\DbManager;

/**
 * Enhanced EmployeeRepository with position/team RBAC lookup methods.
 * 
 * @package Ksfraser\HRM\Repository
 * @since 1.0.0
 */
class EmployeeRepository
{
    private string $table = 'ksf_hrm_employees';
    private string $rbacEmployeeRolesTable = '0_ksf_rbac_employee_roles';
    private DbManager $db;

    public function __construct(DbManager $db)
    {
        $this->db = $db;
    }

    public function findById(int $id): ?Employee
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

    public function findByEmail(string $email): ?Employee
    {
        $row = $this->db->fetchOne(
            "SELECT * FROM {$this->table} WHERE email = ?",
            [$email]
        );

        if (!$row) {
            return null;
        }

        return $this->hydrateFromRow($row);
    }

    public function findByEmployeeNumber(string $employeeNumber): ?Employee
    {
        $row = $this->db->fetchOne(
            "SELECT * FROM {$this->table} WHERE employee_number = ?",
            [$employeeNumber]
        );

        if (!$row) {
            return null;
        }

        return $this->hydrateFromRow($row);
    }

    public function findAll(array $filters = []): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE 1=1";
        $params = [];

        if (isset($filters['status'])) {
            $sql .= " AND status = ?";
            $params[] = $filters['status'];
        }

        if (isset($filters['department'])) {
            $sql .= " AND department = ?";
            $params[] = $filters['department'];
        }

        if (isset($filters['manager_id'])) {
            $sql .= " AND manager_id = ?";
            $params[] = $filters['manager_id'];
        }

        $sql .= " ORDER BY last_name, first_name";

        $rows = $this->db->fetchAll($sql, $params);

        return array_map([$this, 'hydrateFromRow'], $rows);
    }

    public function findActive(): array
    {
        return $this->findAll(['status' => Employee::STATUS_ACTIVE]);
    }

    public function save(Employee $employee): Employee
    {
        if ($employee->getId() === null) {
            return $this->insert($employee);
        }

        return $this->update($employee);
    }

    private function insert(Employee $employee): Employee
    {
        $sql = "INSERT INTO {$this->table} (
            employee_number, first_name, last_name, email, phone,
            department, job_title, status, hire_date, termination_date,
            manager_id, career_manager_id, operations_manager_id, team_id,
            created_at, updated_at
        ) VALUES (
            :employee_number, :first_name, :last_name, :email, :phone,
            :department, :job_title, :status, :hire_date, :termination_date,
            :manager_id, :career_manager_id, :operations_manager_id, :team_id,
            NOW(), NOW()
        )";

        $params = $this->extractParams($employee);
        $this->db->execute($sql, $params);

        $employee->setId((int)$this->db->fetchValue("SELECT LAST_INSERT_ID()"));

        return $employee;
    }

    private function update(Employee $employee): Employee
    {
        $sql = "UPDATE {$this->table} SET
            employee_number = :employee_number,
            first_name = :first_name,
            last_name = :last_name,
            email = :email,
            phone = :phone,
            department = :department,
            job_title = :job_title,
            status = :status,
            hire_date = :hire_date,
            termination_date = :termination_date,
            manager_id = :manager_id,
            career_manager_id = :career_manager_id,
            operations_manager_id = :operations_manager_id,
            team_id = :team_id,
            updated_at = NOW()
        WHERE id = :id";

        $params = $this->extractParams($employee);
        $params['id'] = $employee->getId();
        $this->db->execute($sql, $params);
        return $employee;
    }

    public function delete(int $id): bool
    {
        return $this->db->execute(
            "DELETE FROM {$this->table} WHERE id = ?",
            [$id]
        ) > 0;
    }

    /**
     * Get RBAC role ID directly assigned to an employee (overrides inherited roles)
     */
    public function findEmployeeRbacRoleId(int $employeeId): ?int
    {
        $sql = "SELECT role_id FROM {$this->rbacEmployeeRolesTable} WHERE employee_id = ?";
        $row = $this->db->fetchOne($sql, [$employeeId]);
        
        return $row ? (int)$row['role_id'] : null;
    }

    /**
     * Assign RBAC role to an employee (direct override)
     */
    public function assignEmployeeRbacRole(int $employeeId, int $roleId): bool
    {
        $sql = "INSERT INTO {$this->rbacEmployeeRolesTable} (employee_id, role_id) VALUES (?, ?)";
        return $this->db->execute($sql, [$employeeId, $roleId]) > 0;
    }

    /**
     * Remove RBAC role assignment from employee
     */
    public function revokeEmployeeRoles(int $employeeId): bool
    {
        $sql = "DELETE FROM {$this->rbacEmployeeRolesTable} WHERE employee_id = ?";
        return $this->db->execute($sql, [$employeeId]) > 0;
    }

    /**
     * Get effective RBAC role ID for an employee considering the inheritance hierarchy
     * 
     * Hierarchy: Employee Role > Position Role > HRM Role
     */
    public function getEffectiveEmployeeRbacRoleId(int $employeeId): ?int
    {
        // 1. Check for direct employee RBAC role (highest precedence)
        $directRoleId = $this->findEmployeeRbacRoleId($employeeId);
        if ($directRoleId !== null) {
            return $directRoleId;
        }

        // 2. If no direct role, use position-based role
        $positions = $this->findCurrentPositionsForEmployee($employeeId);
        foreach ($positions as $position) {
            $roleIds = $this->getRoleIdsForPosition($position['position_id']);
            if (!empty($roleIds)) {
                return array_shift($roleIds);
            }
        }

        return null;
    }

    /**
     * Get RBAC role IDs assigned to a position
     */
    private function getRoleIdsForPosition(int $positionId): array
    {
        $sql = "SELECT access_role_id FROM ksf_hrm_position_roles WHERE position_id = ?";
        $rows = $this->db->fetchAll($sql, [$positionId]);
        
        return array_map(function($row) {
            return (int)$row['access_role_id'];
        }, $rows);
    }

    public function findPositionHistory(int $employeeId): array
    {
        $sql = "SELECT ep.*, hrm_positions.name as position_name
                FROM ksf_hrm_employee_positions ep
                JOIN hrm_positions ON ep.position_id = hrm_positions.id
                WHERE ep.employee_id = ?
                ORDER BY ep.start_date DESC";

        $rows = $this->db->fetchAll($sql, [$employeeId]);
        return array_map(function($row) {
            return [
                'id' => (int)$row['id'],
                'employee_id' => (int)$row['employee_id'],
                'position_id' => (int)$row['position_id'],
                'start_date' => $row['start_date'],
                'end_date' => $row['end_date'] ?? null,
                'position_name' => $row['position_name'],
                'team_id' => $row['team_id'] ?? null
            ];
        }, $rows);
    }

    public function findEffectiveTeamIdsForEmployee(int $employeeId): array
    {
        $history = $this->findPositionHistory($employeeId);
        $teamIds = [];

        foreach ($history as $entry) {
            if (!empty($entry['team_id'])) {
                $teamIds[] = (int)$entry['team_id'];
            }
        }

        $directTeam = $this->getDirectTeamId($employeeId);
        if (!empty($directTeam)) {
            $teamIds[] = $directTeam;
        }

        return array_unique($teamIds);
    }

    private function getDirectTeamId(int $employeeId): ?int
    {
        $sql = "SELECT team_id FROM {$this->table} WHERE id = ?";
        $row = $this->db->fetchOne($sql, [$employeeId]);

        if ($row && !empty($row['team_id'])) {
            return (int)$row['team_id'];
        }

        return null;
    }

    public function findCurrentPositionsForEmployee(int $employeeId): array
    {
        $positions = [];
        $positionHistory = $this->findPositionHistory($employeeId);

        foreach ($positionHistory as $entry) {
            if (empty($entry['end_date']) || (new \DateTime()) < (new \DateTime($entry['end_date']))) {
                $positions[] = [
                    'id' => (int)$entry['position_id'],
                    'name' => $entry['position_name'],
                    'team_id' => $entry['team_id'] ?? null
                ];
            }
        }

        return $positions;
    }

    private function hydrateFromRow(array $row): Employee
    {
        $employee = new Employee();
        $employee->setId((int)$row['id']);
        $employee->setEmployeeNumber($row['employee_number'] ?? null);
        $employee->setFirstName($row['first_name']);
        $employee->setLastName($row['last_name']);
        $employee->setEmail($row['email'] ?? null);
        $employee->setPhone($row['phone'] ?? null);
        $employee->setDepartment($row['department'] ?? null);
        $employee->setJobTitle($row['job_title'] ?? null);
        $employee->setStatus($row['status'] ?? Employee::STATUS_ACTIVE);

        if (!empty($row['hire_date'])) {
            $employee->setHireDate(new \DateTime($row['hire_date']));
        }

        if (!empty($row['termination_date'])) {
            $employee->setTerminationDate(new \DateTime($row['termination_date']));
        }

        $employee->setManagerId($row['manager_id'] ? (int)$row['manager_id'] : null);
        $employee->setCareerManagerId($row['career_manager_id'] ? (int)$row['career_manager_id'] : null);
        $employee->setOperationsManagerId($row['operations_manager_id'] ? (int)$row['operations_manager_id'] : null);
        $employee->setTeamId($row['team_id'] ? (int)$row['team_id'] : null);

        return $employee;
    }

    private function extractParams(Employee $employee): array
    {
        return [
            'employee_number' => $employee->getEmployeeNumber(),
            'first_name' => $employee->getFirstName(),
            'last_name' => $employee->getLastName(),
            'email' => $employee->getEmail(),
            'phone' => $employee->getPhone(),
            'department' => $employee->getDepartment(),
            'job_title' => $employee->getJobTitle(),
            'status' => $employee->getStatus(),
            'hire_date' => $employee->getHireDate()?->format('Y-m-d'),
            'termination_date' => $employee->getTerminationDate()?->format('Y-m-d'),
            'manager_id' => $employee->getManagerId(),
            'career_manager_id' => $employee->getCareerManagerId(),
            'operations_manager_id' => $employee->getOperationsManagerId(),
            'team_id' => $employee->getTeamId(),
        ];
    }
}