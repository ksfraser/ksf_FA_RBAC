<?php
declare(strict_types=1);

namespace Ksfraser\FA\CRM\UI\Helpers;

/**
 * Template helpers for rendering the RBAC matrix grid
 * 
 * @package Ksfraser\FA\CRM\UI\Helpers
 * @since 1.0.0
 */
class RbacGridHelpers
{
    /**
     * Scope options for the DDL
     */
    public const SCOPES = ['None', 'Mine', 'Team', 'All'];
    
    /**
     * Permissions in order
     */
    public const PERMISSIONS = ['View', 'Create', 'Edit', 'Delete'];

    /**
     * Get CSS class for a DDL cell based on scope and current setting
     * 
     * @param string $module     Module name (e.g. 'CRM')
     * @param string $permission Permission (e.g. 'View')
     * @param string $scope      Scope (e.g. 'Mine')
     * @param array  $matrix     The access matrix
     * @return string CSS class
     */
    public static function getDdlClass(string $module, string $permission, string $scope, array $matrix): string
    {
        $current = self::getCurrentScopeSetting($module, $permission, $matrix);
        $classes = ['rbac-scope-cell', 'rbac-' . strtolower($scope)];
        
        if ($scope === $current) {
            $classes[] = 'rbac-current';
        }
        
        // Add visual indicator for hierarchy
        $level = array_search($scope, self::SCOPES);
        $currentLevel = array_search($current, self::SCOPES);
        
        if ($level < $currentLevel) {
            $classes[] = 'rbac-inherited';
        } elseif ($level > $currentLevel) {
            $classes[] = 'rbac-enhanced';
        }
        
        return implode(' ', $classes);
    }

    /**
     * Get the currently effective scope for a module/permission from the matrix
     * 
     * @param string $module     Module name
     * @param string $permission Permission
     * @param array  $matrix     The access matrix
     * @return string Current scope
     */
    public static function getCurrentScopeSetting(string $module, string $permission, array $matrix): string
    {
        // Check in order of preference: All > Team > Mine > None
        $preference = ['All', 'Team', 'Mine', 'None'];
        
        foreach ($preference as $scope) {
            $key = "{$permission}_{$scope}";
            if (isset($matrix[$module][$permission][$scope]) && 
                $matrix[$module][$permission][$scope] !== null) {
                return $scope;
            }
        }
        
        return 'None';
    }

    /**
     * Render DDL options for a scope cell
     * 
     * @param string $module     Module name
     * @param string $permission Permission
     * @param string $scope      Current scope
     * @param array  $matrix     The access matrix
     * @return string HTML options
     */
    public static function renderDdlOptions(string $module, string $permission, string $scope, array $matrix): string
    {
        $options = [];
        $current = self::getCurrentScopeSetting($module, $permission, $matrix);
        
        foreach (self::SCOPES as $option) {
            $selected = ($option === $current) ? ' selected="selected"' : '';
            $disabled = self::isScopeDisabled($option, $matrix, $module, $permission) ? ' disabled="disabled"' : '';
            $options[] = "<option value=\"{$option}\"{$selected}{$disabled}>{$option}</option>";
        }
        
        return implode("\n", $options);
    }

    /**
     * Check if a scope should be disabled based on inheritance rules
     * 
     * @param string $scope      The scope to check
     * @param array  $matrix     The access matrix
     * @param string $module     Module name
     * @param string $permission Permission
     * @return bool
     */
    public static function isScopeDisabled(string $scope, array $matrix, string $module, string $permission): bool
    {
        // Disable scopes lower than the most permissive explicit grant
        $preference = ['All', 'Team', 'Mine', 'None'];
        $maxScope   = self::getCurrentScopeSetting($module, $permission, $matrix);
        $maxLevel   = array_search($maxScope, $preference);
        $scopeLevel = array_search($scope, $preference);
        
        // If this scope is more restrictive than the max allowed, disable it
        return $scopeLevel > $maxLevel;
    }

    /**
     * Generate the complete RBAC matrix grid HTML for a module
     * 
     * @param array  $matrix  The access matrix
     * @param string $module  Module name
     * @return string HTML table
     */
    public static function renderMatrixGrid(array $matrix, string $module = 'CRM'): string
    {
        $html  = "<table class='rbac-matrix-grid' data-module='{$module}'>\n";
        $html .= "  <thead>\n";
        $html .= "    <tr>\n";
        $html .= "      <th class='rbac-module-header'>" . esc($module) . "</th>\n";
        
        foreach (self::PERMISSIONS as $perm) {
            $html .= "      <th class='rbac-permission-header' colspan='" . count(self::SCOPES) . "'>" . esc($perm) . "</th>\n";
        }
        $html .= "    </tr>\n";
        $html .= "    <tr>\n";
        $html .= "      <th></th>\n";
        
        foreach (self::PERMISSIONS as $perm) {
            foreach (self::SCOPES as $scope) {
                $html .= "      <th class='rbac-scope-header'>" . esc($scope) . "</th>\n";
            }
        }
        $html .= "    </tr>\n";
        $html .= "  </thead>\n";
        $html .= "  <tbody>\n";
        
        // Single row for the module
        $html .= "    <tr class='rbac-module-row'>\n";
        $html .= "      <td class='rbac-module-name'>" . esc($module) . "</td>\n";
        
        foreach (self::PERMISSIONS as $permission) {
            foreach (self::SCOPES as $scope) {
                $classes   = self::getDdlClass($module, $permission, $scope, $matrix);
                $options   = self::renderDdlOptions($module, $permission, $scope, $matrix);
                $disabled  = self::isScopeDisabled($scope, $matrix, $module, $permission) ? ' disabled' : '';
                
                $html .= "      <td class='{$classes}'>\n";
                $html .= "        <select name=\"rbac[{$module}][{$permission}][{$scope}]\"{$disabled}>\n";
                $html .= "          {$options}\n";
                $html .= "        </select>\n";
                $html .= "      </td>\n";
            }
        }
        $html .= "    </tr>\n";
        
        $html .= "  </tbody>\n";
        $html .= "</table>\n";
        
        return $html;
    }

    /**
     * Render a simplified DDL for a specific CRM filter (Show Mine/Team/All)
     * 
     * @param string $module     Module name
     * @param string $permission Permission (typically 'View')
     * @param array  $matrix     The access matrix
     * @return string HTML select
     */
    public static function renderCrmFilterDdl(string $module, string $permission = 'View', array $matrix): string
    {
        $options = [];
        $current = self::getCurrentScopeSetting($module, $permission, $matrix);
        
        // For CRM filters we only want Mine/Team/All (not None)
        $filterScopes = ['Mine', 'Team', 'All'];
        
        foreach ($filterScopes as $scope) {
            $selected = ($scope === $current) ? ' selected="selected"' : '';
            $options[] = "<option value=\"{$scope}\"{$selected}>{$scope}</option>";
        }
        
        return "<select name=\"crm_filter[{$module}][{$permission}]\" class=\"crm-filter-ddl\">\n" .
               implode("\n", $options) .
               "\n</select>";
    }
}