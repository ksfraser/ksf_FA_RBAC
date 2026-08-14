<?php

namespace Ksfraser\FA\Rbac\Repository;

use Ksfraser\Rbac\Contract\RecordAccessRepositoryInterface;
use Ksfraser\Rbac\Contract\RecordAccessInterface;

/**
 * FA RecordAccessRepository implementation for ksf_FA_RBAC
 */
class FaRecordAccessRepository implements RecordAccessRepositoryInterface {

    protected $dbAdapter;

    public function __construct(\Ksfraser\FA\Rbac\Adapter\FaDbAdapter $dbAdapter) {
        $this->dbAdapter = $dbAdapter;
    }

    public function findForRecord(string $module, string $recordType, int $recordId, array $teamIds): array {
        if (empty($teamIds)) {
            return [];
        }
        
        $placeholders = str_repeat('?,', count($teamIds) - 1) . '?';
        $sql = "SELECT ra.* 
                FROM " . TB_PREF . "rbac_record_access ra 
                WHERE ra.module = ? 
                AND ra.record_type = ? 
                AND ra.record_id = ? 
                AND ra.team_id IN ($placeholders)
                AND ra.inactive = 0";
                
        $params = array_merge([$module, $recordType, $recordId], $teamIds);
        $results = $this->dbAdapter->fetchAll($sql, $params);
        
        $accessRecords = [];
        foreach ($results as $row) {
            $accessRecords[] = new class($row) implements RecordAccessInterface {
                private $data;
                
                public function __construct(array $data) {
                    $this->data = $data;
                }
                
                public function getModule(): string {
                    return $this->data['module'];
                }
                
                public function getRecordType(): string {
                    return $this->data['record_type'];
                }
                
                public function getRecordId(): int {
                    return (int)$this->data['record_id'];
                }
                
                public function getTeamId(): string {
                    return $this->data['team_id'];
                }
                
                public function getProjection(): string {
                    return $this->data['projection'];
                }
                
                public function getCapabilities(): array {
                    return [
                        'can_view' => (bool)$this->data['can_view'],
                        'can_edit' => (bool)$this->data['can_edit'],
                        'can_delete' => (bool)$this->data['can_delete'],
                        'can_export' => (bool)$this->data['can_export'],
                        'can_print' => (bool)$this->data['can_print'],
                        'can_invite' => (bool)$this->data['can_invite'],
                        'can_restore' => (bool)$this->data['can_restore']
                    ];
                }
                
                public function getGrantedBy(): ?string {
                    return $this->data['granted_by'] ?: null;
                }
                
                public function getGrantedAt(): ?string {
                    return $this->data['granted_at'];
                }
                
                public function getExpiresAt(): ?string {
                    return $this->data['expires_at'];
                }
            };
        }
        
        return $accessRecords;
    }

    public function save(RecordAccessInterface $access): void {
        $sql = "INSERT INTO " . TB_PREF . "rbac_record_access 
                (module, record_type, record_id, team_id, projection, 
                 can_view, can_edit, can_delete, can_export, can_print, can_invite, can_restore,
                 granted_by, granted_at, expires_at, inactive) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, 0)
                ON DUPLICATE KEY UPDATE
                projection = VALUES(projection),
                can_view = VALUES(can_view),
                can_edit = VALUES(can_edit),
                can_delete = VALUES(can_delete),
                can_export = VALUES(can_export),
                can_print = VALUES(can_print),
                can_invite = VALUES(can_invite),
                can_restore = VALUES(can_restore),
                granted_by = VALUES(granted_by),
                granted_at = NOW(),
                expires_at = VALUES(expires_at),
                inactive = VALUES(inactive)";
                
        $caps = $access->getCapabilities();
        
        $this->dbAdapter->executeUpdate($sql, [
            $access->getModule(),
            $access->getRecordType(),
            $access->getRecordId(),
            $access->getTeamId(),
            $access->getProjection(),
            $caps['can_view'] ? 1 : 0,
            $caps['can_edit'] ? 1 : 0,
            $caps['can_delete'] ? 1 : 0,
            $caps['can_export'] ? 1 : 0,
            $caps['can_print'] ? 1 : 0,
            $caps['can_invite'] ? 1 : 0,
            $caps['can_restore'] ? 1 : 0,
            $access->getGrantedBy(),
            $access->getExpiresAt()
        ]);
    }

    public function deactivateForTeam(string $module, string $recordType, int $recordId, string $teamId): void {
        $sql = "UPDATE " . TB_PREF . "rbac_record_access 
                SET inactive = 1 
                WHERE module = ? 
                AND record_type = ? 
                AND record_id = ? 
                AND team_id = ?";
                
        $this->dbAdapter->executeUpdate($sql, [$module, $recordType, $recordId, $teamId]);
    }

    public function reassign(string $fromTeamId, string $toTeamId, string $reassignedBy, array $recordIds): int {
        if (empty($recordIds)) {
            return 0;
        }
        
        $placeholders = str_repeat('?,', count($recordIds) - 1) . '?';
        $sql = "UPDATE " . TB_PREF . "rbac_record_access 
                SET team_id = ?, granted_by = ?, granted_at = NOW() 
                WHERE team_id = ? 
                AND record_id IN ($placeholders)";
                
        $params = array_merge([$toTeamId, $reassignedBy, $fromTeamId], $recordIds);
        $stmt = $this->dbAdapter->executeUpdate($sql, $params);
        
        return $stmt;
    }

    public function buildAccessJoinSql(string $module, string $recordType, string $tableAlias): string {
        // Returns SQL JOIN fragment for default-deny enforcement
        $joinSql = "LEFT JOIN " . TB_PREF . "rbac_record_access ra ON "
                . "ra.module = '" . $this->dbAdapter->escapeString($module) . "' "
                . "AND ra.record_type = '" . $this->dbAdapter->escapeString($recordType) . "' "
                . "AND ra.record_id = " . $tableAlias . ".id "
                . "AND ra.team_id IN (
                    SELECT team_id FROM " . TB_PREF . "rbac_team_members 
                    WHERE user_id = @current_user_id AND inactive = 0
                ) "
                . "AND ra.inactive = 0";
                
        return $joinSql;
    }

}