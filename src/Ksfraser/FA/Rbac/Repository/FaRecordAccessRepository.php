<?php

namespace Ksfraser\FA\Rbac\Repository;

use Ksfraser\Rbac\Contract\RecordAccessRepositoryInterface;


/**
 * Default RecordAccessRepository implementation for ksf_FA_RBAC
 */
class FaRecordAccessRepository implements RecordAccessRepositoryInterface {

    protected $dbAdapter;

    public function __construct(\Ksfraser\FA\Rbac\Adapter\FaDbAdapter $dbAdapter) {
        $this->dbAdapter = $dbAdapter;
    }

    public function findForRecord(string $module, string $recordType, int $recordId, array $teamIds): array {
        // Query to get record access for specific record and teams
        return [];
    }

    public function save(RecordAccess $access): void {
        // Implementation needed
        // Would use on_duplicate_key_update to ensure idempotence
    }

    public function deactivateForTeam(string $module, string $recordType, int $recordId, string $teamId): void {
        // Soft-delete record access for a team
    }

    public function reassign(string $fromTeamId, string $toTeamId, string $reassignedBy, array $recordIds): int {
        // Reassign record access from one team to another
        return 0;
    }

    public function buildAccessJoinSql(string $module, string $recordType, string $tableAlias): string {
        // Returns SQL JOIN fragment for default-deny enforcement
        return "";
    }

}