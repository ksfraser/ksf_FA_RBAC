<?php

declare(strict_types=1);

namespace Ksfraser\FA\Rbac\Contract;

/**
 * Minimal database adapter interface for RBAC repositories.
 *
 * Abstracts FA's procedural db_query() API so repositories are testable
 * without a live FA database connection.
 *
 * @since 1.0.0
 */
interface DbAdapterInterface
{
    /**
     * Fetch a single row as an associative array.
     *
     * @param string  $sql    SQL with ? placeholders
     * @param array   $params Parameter values (in order)
     * @return array|null Row data, or null if not found
     *
     * @since 1.0.0
     */
    public function fetchAssoc(string $sql, array $params = []): ?array;

    /**
     * Fetch all rows as an array of associative arrays.
     *
     * @param string $sql    SQL with ? placeholders
     * @param array  $params Parameter values (in order)
     * @return array
     *
     * @since 1.0.0
     */
    public function fetchAll(string $sql, array $params = []): array;

    /**
     * Execute an INSERT, UPDATE, or DELETE statement.
     *
     * @param string $sql    SQL with ? placeholders
     * @param array  $params Parameter values (in order)
     * @return int Number of affected rows
     *
     * @since 1.0.0
     */
    public function executeUpdate(string $sql, array $params = []): int;

    /**
     * Return the last auto-increment ID from an INSERT.
     *
     * @return int
     *
     * @since 1.0.0
     */
    public function lastInsertId(): int;
}
