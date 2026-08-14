<?php

namespace Ksfraser\FA\Rbac\Repository;

use Ksfraser\Rbac\Contract\TeamRepositoryInterface;
use Ksfraser\Rbac\Contract\TeamInterface;
use Ksfraser\Rbac\Contract\TeamMemberInterface;

/**
 * FA TeamRepository implementation for ksf_FA_RBAC
 */
class FaTeamRepository implements TeamRepositoryInterface {

    protected $dbAdapter;

    public function __construct(\Ksfraser\FA\Rbac\Adapter\FaDbAdapter $dbAdapter) {
        $this->dbAdapter = $dbAdapter;
    }

    public function findById(string $teamId): ?TeamInterface {
        $sql = "SELECT id, display_name, team_type, owner_id, auto_managed, requires_approval, inactive 
                FROM " . TB_PREF . "rbac_teams 
                WHERE id = ? AND inactive = 0";
        
        $result = $this->dbAdapter->fetchAssoc($sql, [$teamId]);
        
        if (!$result) {
            return null;
        }
        
        // Return a team object (simplified for now)
        return new class($result) implements TeamInterface {
            private $data;
            
            public function __construct(array $data) {
                $this->data = $data;
            }
            
            public function getId(): string {
                return $this->data['id'];
            }
            
            public function getDisplayName(): string {
                return $this->data['display_name'];
            }
            
            public function getTeamType(): string {
                return $this->data['team_type'];
            }
            
            public function getOwnerId(): ?string {
                return $this->data['owner_id'] ?: null;
            }
            
            public function isAutoManaged(): bool {
                return (bool)$this->data['auto_managed'];
            }
            
            public function requiresApproval(): bool {
                return (bool)$this->data['requires_approval'];
            }
            
            public function isInactive(): bool {
                return (bool)$this->data['inactive'];
            }
        };
    }

    public function save(TeamInterface $team): void {
        $sql = "INSERT INTO " . TB_PREF . "rbac_teams 
                (id, display_name, team_type, owner_id, auto_managed, requires_approval, inactive) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                display_name = VALUES(display_name),
                team_type = VALUES(team_type),
                owner_id = VALUES(owner_id),
                auto_managed = VALUES(auto_managed),
                requires_approval = VALUES(requires_approval),
                inactive = VALUES(inactive)";
                
        $this->dbAdapter->executeUpdate($sql, [
            $team->getId(),
            $team->getDisplayName(),
            $team->getTeamType(),
            $team->getOwnerId(),
            $team->isAutoManaged() ? 1 : 0,
            $team->requiresApproval() ? 1 : 0,
            $team->isInactive() ? 1 : 0
        ]);
    }

    public function findDirectTeamIdsForUser(string $userId): array {
        $sql = "SELECT team_id FROM " . TB_PREF . "rbac_team_members 
                WHERE user_id = ? AND inactive = 0";
        
        $results = $this->dbAdapter->fetchAll($sql, [$userId]);
        $teamIds = [];
        
        foreach ($results as $row) {
            $teamIds[] = $row['team_id'];
        }
        
        return $teamIds;
    }

    public function findEffectiveTeamIdsForUser(string $userId): array {
        // Simplified version that returns direct teams only (recursive lookup would require CTE)
        return $this->findDirectTeamIdsForUser($userId);
    }

    public function addMember(TeamMemberInterface $member): void {
        $sql = "INSERT INTO " . TB_PREF . "rbac_team_members 
                (team_id, user_id, role, approved, added_by, added_at, inactive) 
                VALUES (?, ?, ?, ?, ?, NOW(), 0)
                ON DUPLICATE KEY UPDATE
                role = VALUES(role),
                approved = VALUES(approved),
                added_by = VALUES(added_by),
                added_at = NOW()";
                
        $this->dbAdapter->executeUpdate($sql, [
            $member->getTeamId(),
            $member->getUserId(),
            $member->getRole(),
            $member->isApproved() ? 1 : 0,
            $member->getAddedBy()
        ]);
    }

    public function approveMember(string $teamId, string $userId, string $approvedBy = null): void {
        $sql = "UPDATE " . TB_PREF . "rbac_team_members 
                SET approved = 1, approved_by = ?, approved_at = NOW() 
                WHERE team_id = ? AND user_id = ? AND inactive = 0";
                
        $this->dbAdapter->executeUpdate($sql, [$approvedBy, $teamId, $userId]);
    }

    public function removeMember(string $teamId, string $userId, string $removedBy = null): void {
        $sql = "UPDATE " . TB_PREF . "rbac_team_members 
                SET inactive = 1, removed_by = ?, removed_at = NOW() 
                WHERE team_id = ? AND user_id = ? AND inactive = 0";
                
        $this->dbAdapter->executeUpdate($sql, [$removedBy, $teamId, $userId]);
    }

    public function exists(string $teamId): bool {
        $sql = "SELECT COUNT(*) as count FROM " . TB_PREF . "rbac_teams 
                WHERE id = ? AND inactive = 0";
                
        $result = $this->dbAdapter->fetchAssoc($sql, [$teamId]);
        return $result && (int)$result['count'] > 0;
    }

    public function deactivate(string $teamId): void {
        $sql = "UPDATE " . TB_PREF . "rbac_teams 
                SET inactive = 1 
                WHERE id = ?";
                
        $this->dbAdapter->executeUpdate($sql, [$teamId]);
    }

}