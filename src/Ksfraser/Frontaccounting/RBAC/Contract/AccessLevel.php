<?php
/**
 * Concrete implementation of AccessLevelInterface.
 *
 * Holds permission levels calculated from the RBAC inheritance hierarchy:
 * Employee Role > Position Role > HRM Role.
 *
 * @package Ksfraser\FrontAccounting\RBAC\Contract
 * @since 1.0.0
 */

namespace Ksfraser\FrontAccounting\RBAC\Contract;

/**
 * Access Level Value Object.
 *
 * @package Ksfraser\FrontAccounting\RBAC\Contract
 * @since 1.0.0
 */
class AccessLevel implements AccessLevelInterface
{
    private string $effectiveScope = 'none';
    private int $viewLevel = 0;
    private int $createLevel = 0;
    private int $editLevel = 0;
    private int $deleteLevel = 0;
    private string $recordType = '';
    private ?int $recordId = null;

    /**
     * Create from RBAC role data.
     *
     * Parses the inherited/granted RBAC roles to compute effective levels:
     * 1. Employee RBAC role (highest precedence)
     * 2. Position RBAC role (inherited from HRM position)
     * 3. HRM Role RBAC role (fallback)
     *
     * @param array<int, array{module: string, permission: string, scope: string, record_type?: string, record_id?: int|null}> $rolePermissions
     * @param string $requestedModule Module being checked (e.g. 'CRM')
     * @param string $requestedRecordType Record type (e.g. 'customer')
     * @param int|null $requestedRecordId Optional specific record ID
     */
    public static function fromRolePermissions(
        array $rolePermissions,
        string $requestedModule,
        string $requestedRecordType,
        ?int $requestedRecordId = null
    ): self {
        $instance = new self();
        $instance->recordType = $requestedRecordType;
        $instance->recordId = $requestedRecordId;

        // Apply inheritance order: Employee > Position > Default
        foreach (['employee', 'position', 'default'] as $level) {
            $instance->applyLevelPermissions($rolePermissions, $level, $requestedModule, $requestedRecordType);
        }

        $instance->computeEffectiveScope();

        return $instance;
    }

    /**
     * Apply permissions from a specific inheritance level.
     *
     * @param array<int, array> $rolePermissions
     * @param string $level 'employee', 'position', or 'default'
     * @param string $module
     * @param string $recordType
     */
    private function applyLevelPermissions(array $rolePermissions, string $level, string $module, string $recordType): void
    {
        $levelMap = [
            'employee' => 2,
            'position' => 1,
            'default'  => 0,
        ];

        $levelValue = $levelMap[$level] ?? 0;

        foreach ($rolePermissions as $perm) {
            if ($perm['level'] !== $level) {
                continue;
            }
            if ($perm['module'] !== $module) {
                continue;
            }
            if (!empty($perm['record_type']) && $perm['record_type'] !== $recordType) {
                continue;
            }

            $scopeValue = $this->scopeToInt($perm['scope']);
            $permissionValue = $this->permissionToInt($perm['permission']);

            // Higher level takes precedence for that permission
            $currentValue = $this->getCurrentLevel($perm['permission']);
            if ($levelValue >= $currentValue) {
                $this->setPermissionLevel($perm['permission'], $levelValue, $scopeValue);
            }
        }
    }

    private function scopeToInt(string $scope): int
    {
        return match ($scope) {
            'All'   => 3,
            'Team'  => 2,
            'Mine'  => 1,
            'None'  => 0,
            default => 0,
        };
    }

    private function permissionToInt(string $permission): int
    {
        return match ($permission) {
            'View'   => 1,
            'Create' => 2,
            'Edit'   => 3,
            'Delete' => 4,
            default  => 0,
        };
    }

    /**
     * Track current level for a given permission.
     */
    private int $currentViewLevel = 0;
    private int $currentCreateLevel = 0;
    private int $currentEditLevel = 0;
    private int $currentDeleteLevel = 0;

    private function getCurrentLevel(string $permission): int
    {
        return match ($permission) {
            'View'   => $this->currentViewLevel,
            'Create' => $this->currentCreateLevel,
            'Edit'   => $this->currentEditLevel,
            'Delete' => $this->currentDeleteLevel,
            default  => 0,
        };
    }

    private function setPermissionLevel(string $permission, int $level, int $scope): void
    {
        $this->currentViewLevel = max($this->currentViewLevel, $level);
        $this->currentCreateLevel = max($this->currentCreateLevel, $level);
        $this->currentEditLevel = max($this->currentEditLevel, $level);
        $this->currentDeleteLevel = max($this->currentDeleteLevel, $level);

        switch ($permission) {
            case 'View':
                $this->viewLevel = $scope;
                break;
            case 'Create':
                $this->createLevel = $scope;
                break;
            case 'Edit':
                $this->editLevel = $scope;
                break;
            case 'Delete':
                $this->deleteLevel = $scope;
                break;
        }
    }

    /**
     * Determine the effective scope based on the most restrictive granted permission.
     */
    private function computeEffectiveScope(): void
    {
        $scopes = [$this->viewLevel, $this->createLevel, $this->editLevel, $this->deleteLevel];
        $minScope = min($scopes);

        $this->effectiveScope = match ($minScope) {
            3 => 'all',
            2 => 'team',
            1 => 'mine',
            default => 'none',
        };
    }

    public function getEffectiveScope(): string
    {
        return $this->effectiveScope;
    }

    public function getViewLevel(): int
    {
        return $this->viewLevel;
    }

    public function getCreateLevel(): int
    {
        return $this->createLevel;
    }

    public function getEditLevel(): int
    {
        return $this->editLevel;
    }

    public function getDeleteLevel(): int
    {
        return $this->deleteLevel;
    }

    public function toArray(): array
    {
        return [
            'effective_scope' => $this->effectiveScope,
            'view_level' => $this->viewLevel,
            'create_level' => $this->createLevel,
            'edit_level' => $this->editLevel,
            'delete_level' => $this->deleteLevel,
            'record_type' => $this->recordType,
            'record_id' => $this->recordId,
        ];
    }
}