<?php

declare(strict_types=1);

namespace Ksfraser\FA\Rbac\Repository;

use Ksfraser\FA\Rbac\Contract\DbAdapterInterface;
use Ksfraser\Rbac\Contract\TeamRepositoryInterface;
use Ksfraser\Rbac\Entity\Team;
use Ksfraser\Rbac\Entity\TeamMember;

/**
 * FrontAccounting implementation of TeamRepositoryInterface.
 *
 * Persists and retrieves Team and TeamMember entities against the
 * `0_rbac_teams` and `0_rbac_team_members` FA database tables.
 *
 * @since 1.0.0
 */
class FaTeamRepository implements TeamRepositoryInterface
{
    /** @var DbAdapterInterface */
    private $db;

    /**
     * @param DbAdapterInterface $db
     *
     * @since 1.0.0
     */
    public function __construct(DbAdapterInterface $db)
    {
        $this->db = $db;
    }

    /**
     * {@inheritdoc}
     *
     * @since 1.0.0
     */
    public function findById(string $teamId): ?Team
    {
        $row = $this->db->fetchAssoc(
            "SELECT id, display_name, team_type, owner_id, auto_managed, requires_approval,
                    inactive, created_at, updated_at
             FROM rbac_teams
             WHERE id = ? AND inactive = 0
             LIMIT 1",
            [$teamId]
        );

        if ($row === null) {
            return null;
        }

        return $this->hydrateTeam($row);
    }

    /**
     * {@inheritdoc}
     *
     * @since 1.0.0
     */
    public function save(Team $team): void
    {
        $this->db->executeUpdate(
            "INSERT INTO rbac_teams
                (id, display_name, team_type, owner_id, auto_managed, requires_approval, inactive, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
             ON DUPLICATE KEY UPDATE
                display_name = VALUES(display_name),
                team_type = VALUES(team_type),
                owner_id = VALUES(owner_id),
                auto_managed = VALUES(auto_managed),
                requires_approval = VALUES(requires_approval),
                inactive = VALUES(inactive),
                updated_at = NOW()",
            [
                $team->getId(),
                $team->getDisplayName(),
                $team->getTeamType(),
                $team->getOwnerId(),
                (int) $team->isAutoManaged(),
                (int) $team->requiresApproval(),
                (int) $team->isInactive(),
            ]
        );
    }

    /**
     * {@inheritdoc}
     *
     * @since 1.0.0
     */
    public function deactivate(string $teamId): void
    {
        $this->db->executeUpdate(
            "UPDATE rbac_teams SET inactive = 1, updated_at = NOW() WHERE id = ?",
            [$teamId]
        );
    }

    /**
     * {@inheritdoc}
     *
     * @since 1.0.0
     */
    public function findDirectTeamIdsForUser(string $userId): array
    {
        $rows = $this->db->fetchAll(
            "SELECT tm.team_id
             FROM rbac_team_members tm
             WHERE tm.user_id = ? AND tm.inactive = 0 AND tm.approved = 1",
            [$userId]
        );

        return array_column($rows, 'team_id');
    }

    /**
     * {@inheritdoc}
     *
     * Expands team membership recursively. Currently performs a single query
     * and deduplicates. Recursive CTE expansion is planned for a future release
     * (TODO: recursive team nesting via CTE).
     *
     * @since 1.0.0
     */
    public function findEffectiveTeamIdsForUser(string $userId): array
    {
        $rows = $this->db->fetchAll(
            "SELECT tm.team_id
             FROM rbac_team_members tm
             WHERE tm.user_id = ? AND tm.inactive = 0 AND tm.approved = 1",
            [$userId]
        );

        $ids = array_column($rows, 'team_id');

        // Deduplicate preserving order
        return array_values(array_unique($ids));
    }

    /**
     * {@inheritdoc}
     *
     * @since 1.0.0
     */
    public function addMember(TeamMember $member): void
    {
        $this->db->executeUpdate(
            "INSERT INTO rbac_team_members
                (team_id, user_id, role, approved, added_by, added_at, inactive)
             VALUES (?, ?, 'member', ?, ?, NOW(), 0)",
            [
                $member->getTeamId(),
                $member->getUserId(),
                (int) $member->isApproved(),
                $member->getAddedBy(),
            ]
        );
    }

    /**
     * {@inheritdoc}
     *
     * @since 1.0.0
     */
    public function approveMember(string $teamId, string $userId, string $approvedBy): void
    {
        $this->db->executeUpdate(
            "UPDATE rbac_team_members
             SET approved = 1, approved_by = ?, approved_at = NOW()
             WHERE team_id = ? AND user_id = ? AND inactive = 0",
            [$approvedBy, $teamId, $userId]
        );
    }

    /**
     * {@inheritdoc}
     *
     * @since 1.0.0
     */
    public function removeMember(string $teamId, string $userId, string $removedBy): void
    {
        $this->db->executeUpdate(
            "UPDATE rbac_team_members
             SET inactive = 1, removed_by = ?, removed_at = NOW()
             WHERE team_id = ? AND user_id = ? AND inactive = 0",
            [$removedBy, $teamId, $userId]
        );
    }

    /**
     * {@inheritdoc}
     *
     * @since 1.0.0
     */
    public function exists(string $teamId): bool
    {
        return $this->db->fetchAssoc(
            "SELECT id FROM rbac_teams WHERE id = ? AND inactive = 0 LIMIT 1",
            [$teamId]
        ) !== null;
    }

    /**
     * Hydrate a Team entity from a DB row.
     *
     * @param array $row
     * @return Team
     *
     * @since 1.0.0
     */
    private function hydrateTeam(array $row): Team
    {
        return new Team(
            (string) $row['id'],
            (string) $row['display_name'],
            (string) $row['team_type'],
            isset($row['owner_id']) && $row['owner_id'] !== '' ? (string) $row['owner_id'] : null,
            (bool) ($row['auto_managed'] ?? false),
            (bool) ($row['requires_approval'] ?? false),
            (bool) ($row['inactive'] ?? false),
            new \DateTimeImmutable($row['created_at'] ?? 'now'),
            new \DateTimeImmutable($row['updated_at'] ?? 'now')
        );
    }
}
