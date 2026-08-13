<?php
/**
 * Contract for access level data returned from RBAC queries.
 *
 * @package Ksfraser\FrontAccounting\RBAC\Contract
 * @since 1.0.0
 */

namespace Ksfraser\FrontAccounting\RBAC\Contract;

/**
 * Value Object for access level query results.
 *
 * Contains all scope-level permissions (None/Mine/Team/All) for a given
 * user and resource combination.
 *
 * @package Ksfraser\FrontAccounting\RBAC\Contract
 * @since 1.0.0
 */
class AccessLevelInterface
{
    /**
     * Get the current effective scope for this access level.
     *
     * @return string One of 'none', 'mine', 'team', 'all'
     */
    public function getEffectiveScope(): string;

    /**
     * Get the view permission level for this access (0, 1, or 2 for restricted scope).
     *
     * @return int 0=denied, 1=mine/team/all, 2=all (truncated)
     */
    public function getViewLevel(): int;

    /**
     * Get the create permission level.
     *
     * @return int 0=denied, 1=restricted scope, 2=full scope
     */
    public function getCreateLevel(): int;

    /**
     * Get the edit permission level.
     *
     * @return int 0=denied, 1=restricted scope, 2=full scope
     */
    public function getEditLevel(): int;

    /**
     * Get the delete permission level.
     *
     * @return int 0=denied, 1=restricted scope, 2=full scope
     */
    public function getDeleteLevel(): int;

    /**
     * Return all data as an associative array.
     *
     * @return array
     */
    public function toArray(): array;
}