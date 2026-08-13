<?php
declare(strict_types=1);

namespace Ksfraser\RBAC\Service;

use Ksfraser\RBAC\Repository\RoleRepository;
use Ksfraser\RBAC\Repository\PositionRepository;
use Ksfraser\RBAC\Repository\RecordAccessRepository;
use Ksfraser\RBAC\Contract\TeamRepositoryInterface;
use Ksfraser\RBAC\Contract\RoleRepositoryInterface;
use Ksfraser\Rbac\ValueObject\CapabilitySet;
use Ksfraser\Rbac\ValueObject\ProjectionName;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Ksfraser\RBAC\Logger\NullAuditLogger;
use Ksfraser\RBAC\Logger\AuditLoggerInterface;

class RbacService implements \Ksfraser\Rbac\Contract\RbacServiceInterface
{
    /**
     * @var RoleRepository
     */
    private RoleRepository $roleRepo;

    /**
     * @var PositionRepository
     */
    private PositionRepository $positionRepo;

    /**
     * @var RecordAccessRepository
     */
    private RecordAccessRepository $accessRepo;

    /**
     * @var TeamRepositoryInterface
     */
    private TeamRepositoryInterface $teamRepo;

    /**
     * @var RoleRepositoryInterface
     */
    private RoleRepositoryInterface $roleRepository;

    /**
     * @var ProjectionResolverInterface
     */
    private ProjectionResolverInterface $projectionResolver;

    /**
     * @var EventDispatcherInterface
     */
    private EventDispatcherInterface $eventDispatcher;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var AuditLoggerInterface
     */
    private AuditLoggerInterface $auditLogger;

    /**
     * Service constructor with dependency injection
     */
    public function __construct(
        RoleRepository $roleRepo,
        PositionRepository $positionRepo,
        RecordAccessRepository $accessRepo,
        TeamRepositoryInterface $teamRepo,
        RoleRepositoryInterface $roleRepository,
        ProjectionResolverInterface $projectionResolver,
        EventDispatcherInterface $eventDispatcher,
        AuditLoggerInterface $auditLogger,
        LoggerInterface $logger = null
    ) {
        $this->roleRepo                  = $roleRepo;
        $this->positionRepo              = $positionRepo;
        $this->accessRepo                = $accessRepo;
        $this->teamRepo                  = $teamRepo;
        $this->roleRepository            = $roleRepository;
        $this->projectionResolver        = $projectionResolver;
        $this->eventDispatcher           = $eventDispatcher;
        $this->auditLogger               = $auditLogger;
        $this->logger                    = $logger ?? new NullAuditLogger();
    }

    /**
     * Get access matrix for all modules, permissions, and scopes
     * 
     * This returns a three-dimensional array suitable for generating the
     * SuiteCRM-style matrix grid.
     * 
     * Format: $matrix[$module][$permission][$scope] = CapabilitySet|null
     * 
     * @return array<mixed>
     */
    public function getAccessMatrix(): array
    {
        // Get all modules by querying distinct module names
        $modules = $this->getDistinctModules();
        $matrix  = [];

        foreach ($modules as $module) {
            $matrix[$module] = $this->getModuleAccessMatrix($module);
        }

        return $matrix;
    }

    /**
     * Get access matrix for a specific module
     * 
     * Returns a two-dimensional array: $matrix[$permission][$scope] = CapabilitySet|null
     * 
     * @param string $module The module to get access matrix for (e.g. 'CRM')
     * @return array<mixed>
     */
    public function getModuleAccessMatrix(string $module): array
    {
        $permissions = ['View', 'Create', 'Edit', 'Delete'];
        $scopes      = ['None', 'Mine', 'Team', 'All'];
        $matrix      = [];

        // Initialize matrix with null values
        foreach ($permissions as $permission) {
            foreach ($scopes as $scope) {
                $matrix[$permission][$scope] = null;
            }
        }

        // Get all explicit role grants for this module
        $explicitRoles = $this->roleRepo->findByPermission($module, '*'); // All permissions

        // Convert to capability sets by scope and permission
        foreach ($explicitRoles as $role) {
            $permission = $role['permission'];
            $scope      = $role['scope'];
            $capabilities = CapabilitySet::fromArray([
                'view'    => $role['record_type'] === 'customer' && str_contains($role['module'], 'crm') ? 1 : 0,
                'create'  => $role['record_type'] === 'customer' && str_contains($role['module'], 'crm') ? 1 : 0,
                'edit'    => $role['record_type'] === 'customer' && str_contains($role['module'], 'crm') ? 1 : 0,
                'delete'  => $role['record_type'] === 'customer' && str_contains($role['module'], 'crm') ? 1 : 0,
            ]);

            // Store in matrix
            if (!isset($matrix[$permission][$scope])) {
                $matrix[$permission][$scope] = $capabilities;
            } else {
                // Merge capabilities (union - most permissive)
                $matrix[$permission][$scope] = $matrix[$permission][$scope]->union($capabilities);
            }
        }

        // Apply inheritance hierarchy:
        // 1. HRM Role + Position (merged with most permissive)
        // 2. Employee RBAC role (overrides the merged result)
        // This would normally involve complex joins with HRM tables
        // For now we'll return the explicit roles as-is
        // The full inheritance logic will be implemented in the getEffectiveAccess methods

        return $matrix;
    }

    /**
     * Get the effective access capabilities for a user on a specific record
     * 
     * This method handles the inheritance hierarchy:
     * - HRM Role and Position permissions are merged (most permissive wins)
     * - Employee RBAC role overrides the merged result
     * 
     * @param string $userId    The user ID
     * @param string $module    The module (e.g. 'CRM')
     * @param string $recordType The record type (e.g. 'customer')
     * @param int    $recordId  The record ID
     * @return CapabilitySet
     */
    public function getEffectiveAccess(string $userId, string $module, string $recordType, int $recordId): CapabilitySet
    {
        // Get the user's HRM role-based capabilities
        $hrmCapabilities = $this->getHrmRoleCapabilities($userId, $module, $recordType, $recordId);

        // Get the user's position-based capabilities
        $positionCapabilities = $this->getPositionCapabilities($userId, $module, $recordType, $recordId);

        // Merge HRM role and position (most permissive wins)
        $mergedCapabilities = $hrmCapabilities->union($positionCapabilities);

        // Get the user's explicit employee RBAC capabilities (overrides everything)
        $employeeCapabilities = $this->getEmployeeCapabilities($userId, $module, $recordType, $recordId);

        // Employee capabilities override the merged HRM+Position result
        return $employeeCapabilities->union($mergedCapabilities); // This ensures employee overrides take precedence
    }

    /**
     * Get capabilities from HRM role assignments
     * 
     * @param string $userId    The user ID
     * @param string $module    The module
     * @param string $recordType The record type
     * @param int    $recordId  The record ID
     * @return CapabilitySet
     */
    private function getHrmRoleCapabilities(string $userId, string $module, string $recordType, int $recordId): CapabilitySet
    {
        // Implementation would join with HRM roles table
        // For now, return none
        return CapabilitySet::none();
    }

    /**
     * Get capabilities from position assignments
     * 
     * @param string $userId    The user ID
     * @param string $module    The module
     * @param string $recordType The record type
     * @param int    $recordId  The record ID
     * @return CapabilitySet
     */
    private function getPositionCapabilities(string $userId, string $module, string $recordType, int $recordId): CapabilitySet
    {
        // Implementation would join with position-to-role mappings
        // For now, return none
        return CapabilitySet::none();
    }

    /**
     * Get capabilities from explicit employee RBAC role assignments
     * 
     * @param string $userId    The user ID
     * @param string $module    The module
     * @param string $recordType The record type
     * @param int    $recordId  The record ID
     * @return CapabilitySet
     */
    private function getEmployeeCapabilities(string $userId, string $module, string $recordType, int $recordId): CapabilitySet
    {
        // Implementation would join with employee_roles table
        // For now, return none
        return CapabilitySet::none();
    }
}