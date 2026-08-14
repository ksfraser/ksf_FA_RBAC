<?php

declare(strict_types=1);

/**
 * Namespaced stubs for the FA procedural functions used by FaDbAdapter.
 *
 * FaDbAdapter (Ksfraser\FrontAccounting\Rbac\Adapter) calls db_query(),
 * db_fetch_assoc(), db_num_affected_rows(), db_insert_id() and
 * mysqli_real_escape_string() as unqualified names inside its own
 * namespace, so PHP resolves them here before falling back to the
 * global (FA/mysqli) functions. This lets the adapter be unit-tested
 * without a live FA database connection.
 *
 * @package Ksfraser\FrontAccounting\Rbac\Adapter
 * @since 1.0.0
 */

namespace Ksfraser\FrontAccounting\Rbac\Adapter;

use Ksfraser\Tests\FrontAccounting\Rbac\Unit\Adapter\AdapterState;
use Ksfraser\Tests\FrontAccounting\Rbac\Unit\Adapter\FakeResult;

function db_query($sql)
{
    AdapterState::$lastQuery = $sql;

    if (AdapterState::$queryReturnsNull) {
        return null;
    }

    $rows = AdapterState::$queryResults[$sql]
        ?? AdapterState::$queryResults['__default__']
        ?? [];

    return new FakeResult($rows);
}

function db_fetch_assoc($result)
{
    if (!$result instanceof FakeResult) {
        return false;
    }

    return $result->next();
}

function db_num_affected_rows()
{
    return AdapterState::$affectedRows;
}

function db_insert_id()
{
    return AdapterState::$insertId;
}

function mysqli_real_escape_string($db, $value)
{
    return (string) $value;
}
