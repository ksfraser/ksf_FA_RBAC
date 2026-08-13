<?php

namespace Ksfraser\FA\Rbac\Repository;

use Ksfraser\Rbac\Contract\TeamRepositoryInterface;


/**
 * Default TeamRepository implementation for ksf_FA_RBAC
 */
class FaTeamRepository implements TeamRepositoryInterface {

    protected $dbAdapter;

    public function __construct(\Ksfraser\FA\Rbac\Adapter\FaDbAdapter $dbAdapter) {
        $this->dbAdapter = $dbAdapter;
    }

    public function findById(string $teamId): ?Team {
        // Implementation needed
        return null;
    }

    public function save(Team $team): void {
        // Implementation needed
        // Would use on_duplicate_key_update to ensure idempotence
    }

    public function findDirectTeamIdsForUser(string $userId): array {
        // Query to get all direct team IDs for a user
        return [];
    }

    public function findEffectiveTeamIdsForUser(string $userId): array {
        // Query to get effective team IDs (including nested/cached)
        return [];
    }

    public function addMember(TeamMember $member): void {
        // Implementation needed
    }

    public function approveMember(string $teamId, string $userId, string $approvedBy = null): void {
        // Implementation needed
    }

    public function removeMember(string $teamId, string $userId, string $removedBy = null): void {
        // Implementation needed
        // Would set inactive flag or delete row
    }

    public function exists(string $teamId): bool {
        // Quick existence check
        return false;
    }

    public function deactivate(string $teamId): void {
        // Soft-delete team
    }

}