<?php

declare(strict_types=1);

namespace Ksfraser\FA\Rbac\Adapter;

use Ksfraser\FA\Rbac\Contract\DbAdapterInterface;

/**
 * FrontAccounting database adapter for RBAC repositories.
 *
 * Bridges repository calls (DbAdapterInterface) with FA's procedural
 * db_query() API. Parameter binding uses mysqli_real_escape_string()
 * since FA does not support prepared statements.
 *
 * @since 1.0.0
 */
class FaDbAdapter implements DbAdapterInterface
{
    /** @var string FA table prefix (TB_PREF value, e.g. "0_") */
    private $tablePrefix;

    /**
     * @param string $tablePrefix FA table prefix (value of TB_PREF constant)
     *
     * @since 1.0.0
     */
    public function __construct(string $tablePrefix = '')
    {
        $this->tablePrefix = $tablePrefix;
    }

    /**
     * Prefix FA core and custom table names.
     *
     * Uses a word-boundary regex so already-prefixed names are not
     * double-prefixed.
     *
     * @param string $sql
     * @return string
     *
     * @since 1.0.0
     */
    private function prefixTables(string $sql): string
    {
        if ($this->tablePrefix === '') {
            return $sql;
        }

        // Prefix 0_rbac_* tables and crm_* tables.
        $sql = preg_replace('/\b(rbac_teams|rbac_team_members|rbac_record_access|rbac_audit_log)\b/', $this->tablePrefix . '$1', $sql);
        $sql = preg_replace('/\b(crm_persons|crm_contacts|crm_categories)\b/', $this->tablePrefix . '$1', $sql);
        $sql = preg_replace('/\busers\b/', $this->tablePrefix . 'users', $sql);

        return $sql;
    }

    /**
     * Substitute ? placeholders with safely escaped values.
     *
     * @param string $sql
     * @param array  $params
     * @return string
     *
     * @since 1.0.0
     */
    private function bindParams(string $sql, array $params): string
    {
        if (empty($params)) {
            return $sql;
        }

        global $db;

        $parts = explode('?', $sql);

        if (count($parts) !== count($params) + 1) {
            return $sql;
        }

        $result = $parts[0];
        foreach ($params as $i => $value) {
            if ($value === null) {
                $result .= 'NULL';
            } elseif (is_int($value) || is_float($value)) {
                $result .= $value;
            } else {
                $escaped = isset($db)
                    ? mysqli_real_escape_string($db, (string) $value)
                    : addslashes((string) $value);
                $result .= "'" . $escaped . "'";
            }
            $result .= $parts[$i + 1];
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     *
     * @since 1.0.0
     */
    public function fetchAssoc(string $sql, array $params = []): ?array
    {
        $sql    = $this->prefixTables($sql);
        $sql    = $this->bindParams($sql, $params);
        $result = db_query($sql);

        if (!$result) {
            return null;
        }

        $row = db_fetch_assoc($result);

        return $row !== false ? $row : null;
    }

    /**
     * {@inheritdoc}
     *
     * @since 1.0.0
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        $sql    = $this->prefixTables($sql);
        $sql    = $this->bindParams($sql, $params);
        $result = db_query($sql);

        if (!$result) {
            return [];
        }

        $rows = [];
        while ($row = db_fetch_assoc($result)) {
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * {@inheritdoc}
     *
     * @since 1.0.0
     */
    public function executeUpdate(string $sql, array $params = []): int
    {
        $sql = $this->prefixTables($sql);
        $sql = $this->bindParams($sql, $params);
        db_query($sql);

        return db_num_affected_rows();
    }

    /**
     * {@inheritdoc}
     *
     * @since 1.0.0
     */
    public function lastInsertId(): int
    {
        return (int) db_insert_id();
    }
}
