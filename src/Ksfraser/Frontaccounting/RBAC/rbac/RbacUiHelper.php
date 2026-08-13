<?php
declare(strict_types=1);

namespace Ksfraser\FA\CRM\UI;

/**
 * CRM UI helpers for displaying the RBAC access matrix
 * 
 * @package Ksfraser\FA\CRM\UI
 * @since 1.0.0
 */
class RbacUiHelper
{
    /**
     * Render the RBAC access matrix for a CRM module
     * 
     * @param array  $matrix  The access matrix (module -> permission -> scope -> CapabilitySet)
     * @param string $module  Module name (e.g. 'CRM')
     * @return string HTML
     */
    public static function renderAccessMatrix(array $matrix, string $module = 'CRM'): string
    {
        return Ksfraser\FA\CRM\UI\Helpers::renderMatrixGrid($matrix, $module);
    }

    /**
     * Render the CRM filter dropdown for accessing records
     * 
     * @param string $module    Module name
     * @param string $permission Permission filter
     * @return string HTML
     */
    public static function renderCrmFilterDdl(string $module, string $permission = 'View', array $matrix): string
    {
        return Ksfraser\FA\CRM\UI\Helpers::renderCrmFilterDdl($module, $permission, $matrix);
    }

    /**
     * Get the current scope setting for a module/permission combination
     * 
     * @param string $module    Module name
     * @param string $permission Permission
     * @param array  $matrix    The access matrix
     * @return string Current scope
     */
    public static function getCurrentScope(string $module, string $permission, array $matrix): string
    {
        return Ksfraser\FA\CRM\UI\Helpers::getCurrentScopeSetting($module, $permission, $matrix);
    }

    /**
     * Get the effective access capabilities for a user
     * 
     * @param string $userId    User ID
     * @param string $module    Module name
     * @param string $recordType Record type
     * @param int    $recordId  Record ID
     * @return array CapabilitySet
     */
    public static function getUserAccessCapabilities(string $userId, string $module, string $recordType, int $recordId): array
    {
        // This would typically call the RbacService
        // For now returning a mock structure
        return [
            'View' => [
                'Mine' => [
                    Ksfraser\RBAC\ValueObject\CapabilitySet::view(),
                    'Team' => [
                        Ksfraser\RBAC\ValueObject\CapabilitySet::view(),
                        Ksfraser\RBAC\ValueObject\CapabilitySet::create(),
                        Ksfraser\RBAC\ValueObject\CapabilitySet::edit(),
                        Ksfraser\RBAC\ValueObject\CapabilitySet::delete()
                    ],
                    'All' => [
                        Ksfraser\RBAC\ValueObject\CapabilitySet::view(),
                        Ksfraser\RBAC\ValueObject\CapabilitySet::create(),
                        Ksfraser\RBAC\ValueObject\CapabilitySet::edit(),
                        Ksfraser\RBAC\ValueObject\CapabilitySet::delete()
                    ]
                ],
                'Mine' => [
                    Ksfraser\RBAC\ValueObject\CapabilitySet::view(),
                    Ksfraser\RBAC\ValueObject\CapabilitySet::create(),
                    Ksfraser\RBAC\ValueObject\CapabilitySet::edit(),
                    Ksfraser\RBAC\ValueObject\CapabilitySet::delete()
                ],
                'Team' => [
                    Ksfraser\RBAC\ValueObject\CapabilitySet::view(),
                    Ksfraser\RBAC\ValueObject\CapabilitySet::create(),
                    Ksfraser\RBAC\ValueObject\CapabilitySet::edit(),
                    Ksfraser\RBAC\ValueObject\CapabilitySet::delete()
                ],
                'All' => [
                    Ksfraser\RBAC\ValueObject\CapabilitySet::view(),
                    Ksfraser\RBAC\ValueObject\CapabilitySet::create(),
                    Ksfraser\RBAC\ValueObject\CapabilitySet::edit(),
                    Ksfraser\RBAC\ValueObject\CapabilitySet::delete()
                ]
            ]
        };
    }
}