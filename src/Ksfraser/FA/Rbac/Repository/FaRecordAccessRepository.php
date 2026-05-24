<?php

declare(strict_types=1);

namespace Ksfraser\FA\Rbac\Repository;

use Ksfraser\FA\Rbac\Contract\DbAdapterInterface;
use Ksfraser\Rbac\Contract\RecordAccessRepositoryInterface;
use Ksfraser\Rbac\Entity\RecordAccess;
use Ksfraser\Rbac\ValueObject\CapabilitySet;
use Ksfraser\Rbac\ValueObject\ProjectionName;

/**
 * FrontAccounting implementation of RecordAccessRepositoryInterface.
 *
 * Persists and retrieves RecordAccess xref rows from `0_rbac_record_access`.
 * Builds the SQL JOIN fragment used by all record-fetching queries to enforce
 * default-deny at the database layer.
 *
 * @since 1.0.0
 */
class FaRecordAccessRepository implements RecordAccessRepositoryInterface
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
    public function findForRecord(
        string $module,
        string $recordType,
        int $recordId,
        array $teamIds
    ): array {
        if (empty($teamIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($teamIds), '?'));
        $params       = array_merge([$module, $recordType, $recordId], $teamIds);

        $rows = $this->db->fetchAll(
            "SELECT id, module, record_type, record_id, team_id, projection,
                    can_view, can_edit, can_delete, can_export, can_print, can_invite, can_restore,
                    granted_by, granted_at, expires_at, inactive
             FROM rbac_record_access
             WHERE module = ?
               AND record_type = ?
               AND record_id = ?
               AND team_id IN ({$placeholders})
               AND inactive = 0
               AND (expires_at IS NULL OR expires_at > NOW())",
            $params
        );

        return array_map([$this, 'hydrateRecordAccess'], $rows);
    }

    /**
     * {@inheritdoc}
     *
     * @since 1.0.0
     */
    public function save(RecordAccess $access): void
    {
        $caps = $access->getCapabilities()->toArray();

        $this->db->executeUpdate(
            "INSERT INTO rbac_record_access
                (module, record_type, record_id, team_id, projection,
                 can_view, can_edit, can_delete, can_export, can_print, can_invite, can_restore,
                 granted_by, granted_at, expires_at, inactive)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, 0)",
            [
                $access->getModule(),
                $access->getRecordType(),
                $access->getRecordId(),
                $access->getTeamId(),
                $access->getProjection()->getValue(),
                (int) $caps['can_view'],
                (int) $caps['can_edit'],
                (int) $caps['can_delete'],
                (int) $caps['can_export'],
                (int) $caps['can_print'],
                (int) $caps['can_invite'],
                (int) $caps['can_restore'],
                $access->getGrantedBy(),
                $access->getExpiresAt() ? $access->getExpiresAt()->format('Y-m-d H:i:s') : null,
            ]
        );
    }

    /**
     * {@inheritdoc}
     *
     * @since 1.0.0
     */
    public function deactivateForTeam(
        string $module,
        string $recordType,
        int $recordId,
        string $teamId
    ): void {
        $this->db->executeUpdate(
            "UPDATE rbac_record_access
             SET inactive = 1
             WHERE module = ? AND record_type = ? AND record_id = ? AND team_id = ? AND inactive = 0",
            [$module, $recordType, $recordId, $teamId]
        );
    }

    /**
     * {@inheritdoc}
     *
     * @since 1.0.0
     */
    public function reassign(
        string $fromTeamId,
        string $toTeamId,
        string $reassignedBy,
        array $recordIds,
        string $module,
        string $recordType
    ): int {
        $params = [$module, $recordType, $fromTeamId];

        if (!empty($recordIds)) {
            $placeholders = implode(',', array_fill(0, count($recordIds), '?'));
            $extraWhere   = " AND record_id IN ({$placeholders})";
            $params       = array_merge($params, $recordIds);
        } else {
            $extraWhere = '';
        }

        $rows = $this->db->fetchAll(
            "SELECT id, module, record_type, record_id, team_id, projection,
                    can_view, can_edit, can_delete, can_export, can_print, can_invite, can_restore,
                    granted_by, granted_at, expires_at, inactive
             FROM rbac_record_access
             WHERE module = ? AND record_type = ? AND team_id = ? AND inactive = 0" . $extraWhere,
            $params
        );

        foreach ($rows as $row) {
            // Deactivate old row
            $this->db->executeUpdate(
                "UPDATE rbac_record_access SET inactive = 1 WHERE id = ?",
                [(int) $row['id']]
            );

            // Insert new row for target team
            $caps = CapabilitySet::fromArray($row);
            $newAccess = new RecordAccess(
                (string) $row['module'],
                (string) $row['record_type'],
                (int) $row['record_id'],
                $toTeamId,
                new ProjectionName((string) $row['projection']),
                $caps,
                $reassignedBy,
                null,
                isset($row['expires_at']) && $row['expires_at'] !== null
                    ? new \DateTimeImmutable((string) $row['expires_at'])
                    : null
            );
            $this->save($newAccess);
        }

        return count($rows);
    }

    /**
     * {@inheritdoc}
     *
     * Returns a SQL JOIN fragment that enforces default-deny at the query layer.
     * The fragment contains ONE ? placeholder for the current user's ID.
     * Callers must bind the user_id parameter when executing the query.
     *
     * Example usage:
     * ```sql
     * SELECT e.*
     * FROM fa_cal_entries e
     * INNER JOIN rbac_record_access ra ON ra.record_id = e.id ...
     * INNER JOIN rbac_team_members tm ON tm.team_id = ra.team_id AND tm.user_id = ?
     * WHERE e.start_date BETWEEN ? AND ?
     * ```
     *
     * @since 1.0.0
     */
    public function buildAccessJoinSql(
        string $module,
        string $recordType,
        string $tableAlias = 'r'
    ): string {
        return "INNER JOIN rbac_record_access ra"
            . "    ON ra.record_id = {$tableAlias}.id"
            . "   AND ra.module = '" . addslashes($module) . "'"
            . "   AND ra.record_type = '" . addslashes($recordType) . "'"
            . "   AND ra.inactive = 0"
            . "   AND (ra.expires_at IS NULL OR ra.expires_at > NOW())"
            . " INNER JOIN rbac_team_members tm"
            . "    ON tm.team_id = ra.team_id"
            . "   AND tm.user_id = ?"
            . "   AND tm.inactive = 0"
            . "   AND tm.approved = 1";
    }

    /**
     * Hydrate a RecordAccess entity from a DB row.
     *
     * @param array $row
     * @return RecordAccess
     *
     * @since 1.0.0
     */
    private function hydrateRecordAccess(array $row): RecordAccess
    {
        return new RecordAccess(
            (string) $row['module'],
            (string) $row['record_type'],
            (int) $row['record_id'],
            (string) $row['team_id'],
            new ProjectionName((string) $row['projection']),
            CapabilitySet::fromArray($row),
            isset($row['granted_by']) ? (string) $row['granted_by'] : null,
            isset($row['granted_at']) ? new \DateTimeImmutable((string) $row['granted_at']) : null,
            isset($row['expires_at']) && $row['expires_at'] !== null
                ? new \DateTimeImmutable((string) $row['expires_at'])
                : null,
            (bool) ($row['inactive'] ?? false),
            isset($row['id']) ? (int) $row['id'] : null
        );
    }
}
