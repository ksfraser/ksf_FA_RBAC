<?php

declare(strict_types=1);

namespace Ksfraser\Tests\FrontAccounting\Rbac\Unit\Adapter;

use PHPUnit\Framework\TestCase;
use Ksfraser\FrontAccounting\Rbac\Adapter\FaDbAdapter;

require_once __DIR__ . '/../../fa_adapter_stubs.php';

/**
 * Fake mysqli-style result set backing the db_fetch_assoc() stub.
 *
 * @package Ksfraser\Tests\FrontAccounting\Rbac\Unit\Adapter
 * @since 1.0.0
 */
class FakeResult
{
    /** @var array */
    private $rows;

    /**
     * @param array $rows Rows to yield, one per next() call.
     *
     * @since 1.0.0
     */
    public function __construct(array $rows)
    {
        $this->rows = $rows;
    }

    /**
     * Return the next row, or false once exhausted.
     *
     * @return array|false
     *
     * @since 1.0.0
     */
    public function next()
    {
        if (empty($this->rows)) {
            return false;
        }

        return array_shift($this->rows);
    }
}

/**
 * Mutable state for the FA procedural-function stubs.
 *
 * @package Ksfraser\Tests\FrontAccounting\Rbac\Unit\Adapter
 * @since 1.0.0
 */
class AdapterState
{
    /** @var array<string, array> Query results keyed by final SQL; '__default__' fallback. */
    public static $queryResults = [];

    /** @var bool When true, db_query() returns null. */
    public static $queryReturnsNull = false;

    /** @var int Value returned by db_num_affected_rows(). */
    public static $affectedRows = 0;

    /** @var int Value returned by db_insert_id(). */
    public static $insertId = 0;

    /** @var string Last SQL passed to db_query(). */
    public static $lastQuery = '';

    /**
     * Reset all stub state between tests.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public static function reset(): void
    {
        self::$queryResults     = [];
        self::$queryReturnsNull = false;
        self::$affectedRows     = 0;
        self::$insertId         = 0;
        self::$lastQuery        = '';
    }
}

/**
 * Unit tests for FaDbAdapter.
 *
 * Exercises the FA procedural adapter against namespaced function stubs —
 * no live FA database connection required.
 *
 * @covers \Ksfraser\FrontAccounting\Rbac\Adapter\FaDbAdapter
 * @since 1.0.0
 */
class FaDbAdapterTest extends TestCase
{
    /**
     * @return void
     *
     * @since 1.0.0
     */
    protected function setUp(): void
    {
        AdapterState::reset();
        $GLOBALS['db'] = new \stdClass();
    }

    /**
     * @return void
     *
     * @since 1.0.0
     */
    protected function tearDown(): void
    {
        unset($GLOBALS['db']);
    }

    // -------------------------------------------------------------------------
    // prefixTables
    // -------------------------------------------------------------------------

    /**
     * @test
     * @since 1.0.0
     */
    public function testEmptyPrefixLeavesSqlUnchanged(): void
    {
        $adapter = new FaDbAdapter('');
        $row     = $adapter->fetchAssoc('SELECT * FROM rbac_teams WHERE id = ?', ['x']);

        $this->assertNull($row);
        $this->assertStringContainsString('rbac_teams', AdapterState::$lastQuery);
        $this->assertStringNotContainsString('0_rbac_teams', AdapterState::$lastQuery);
    }

    /**
     * @test
     * @since 1.0.0
     */
    public function testPrefixTablesPrefixesRbacAndCrmTables(): void
    {
        $adapter = new FaDbAdapter('0_');
        $adapter->fetchAssoc('SELECT * FROM rbac_teams WHERE id = ?', ['x']);

        $this->assertStringContainsString('0_rbac_teams', AdapterState::$lastQuery);
    }

    /**
     * @test
     * @since 1.0.0
     */
    public function testPrefixTablesPrefixesUsersTable(): void
    {
        $adapter = new FaDbAdapter('0_');
        $adapter->fetchAssoc('SELECT id FROM users WHERE id = ?', ['1']);

        $this->assertStringContainsString('0_users', AdapterState::$lastQuery);
    }

    // -------------------------------------------------------------------------
    // bindParams
    // -------------------------------------------------------------------------

    /**
     * @test
     * @since 1.0.0
     */
    public function testBindParamsTreatsNullAsSqlNull(): void
    {
        $adapter = new FaDbAdapter('0_');
        $adapter->fetchAssoc('SELECT * FROM rbac_record_access WHERE expires_at = ?', [null]);

        $this->assertStringContainsString('expires_at = NULL', AdapterState::$lastQuery);
    }

    /**
     * @test
     * @since 1.0.0
     */
    public function testBindParamsInterpolatesIntegerAndFloatRaw(): void
    {
        $adapter = new FaDbAdapter('0_');
        $adapter->fetchAssoc('SELECT * FROM rbac_record_access WHERE record_id = ? AND n = ?', [42, 1.5]);

        $this->assertStringContainsString('record_id = 42', AdapterState::$lastQuery);
        $this->assertStringContainsString('n = 1.5', AdapterState::$lastQuery);
    }

    /**
     * @test
     * @since 1.0.0
     */
    public function testBindParamsQuotesAndEscapesString(): void
    {
        $adapter = new FaDbAdapter('0_');
        $adapter->fetchAssoc('SELECT * FROM rbac_teams WHERE id = ?', ['sales_team']);

        $this->assertStringContainsString("id = 'sales_team'", AdapterState::$lastQuery);
    }

    /**
     * @test
     * @since 1.0.0
     */
    public function testBindParamsMismatchedCountLeavesSqlUnchanged(): void
    {
        $adapter = new FaDbAdapter('0_');
        $adapter->fetchAssoc('SELECT * FROM rbac_teams WHERE id = ? AND x = ?', ['only_one']);

        $this->assertStringContainsString('?', AdapterState::$lastQuery);
        $this->assertStringContainsString('AND x = ?', AdapterState::$lastQuery);
    }

    /**
     * @test
     * @since 1.0.0
     */
    public function testBindParamsEmptyParamsReturnsSqlUnchanged(): void
    {
        $adapter = new FaDbAdapter('0_');
        $adapter->fetchAssoc('SELECT * FROM rbac_teams WHERE inactive = 0');

        $this->assertStringContainsString('inactive = 0', AdapterState::$lastQuery);
        $this->assertStringNotContainsString('NULL', AdapterState::$lastQuery);
    }

    /**
     * @test
     * @since 1.0.0
     */
    public function testBindParamsUsesAddslashesWhenNoDbGlobal(): void
    {
        unset($GLOBALS['db']);
        $adapter = new FaDbAdapter('0_');
        $adapter->fetchAssoc('SELECT * FROM rbac_teams WHERE id = ?', ["O'Reilly"]);

        $this->assertStringContainsString("'O\\'Reilly'", AdapterState::$lastQuery);
    }

    // -------------------------------------------------------------------------
    // fetchAssoc
    // -------------------------------------------------------------------------

    /**
     * @test
     * @since 1.0.0
     */
    public function testFetchAssocReturnsRow(): void
    {
        AdapterState::$queryResults['__default__'] = [['id' => '1', 'name' => 'Sales']];

        $adapter = new FaDbAdapter('0_');
        $row     = $adapter->fetchAssoc('SELECT id, name FROM rbac_teams WHERE id = ?', ['1']);

        $this->assertSame(['id' => '1', 'name' => 'Sales'], $row);
    }

    /**
     * @test
     * @since 1.0.0
     */
    public function testFetchAssocReturnsNullWhenQueryFails(): void
    {
        AdapterState::$queryReturnsNull = true;

        $adapter = new FaDbAdapter('0_');
        $row     = $adapter->fetchAssoc('SELECT id FROM rbac_teams WHERE id = ?', ['1']);

        $this->assertNull($row);
    }

    /**
     * @test
     * @since 1.0.0
     */
    public function testFetchAssocReturnsNullWhenNoRow(): void
    {
        AdapterState::$queryResults['__default__'] = [];

        $adapter = new FaDbAdapter('0_');
        $row     = $adapter->fetchAssoc('SELECT id FROM rbac_teams WHERE id = ?', ['nope']);

        $this->assertNull($row);
    }

    // -------------------------------------------------------------------------
    // fetchAll
    // -------------------------------------------------------------------------

    /**
     * @test
     * @since 1.0.0
     */
    public function testFetchAllReturnsRows(): void
    {
        AdapterState::$queryResults['__default__'] = [
            ['team_id' => '5_individual'],
            ['team_id' => 'sales_team'],
        ];

        $adapter = new FaDbAdapter('0_');
        $rows    = $adapter->fetchAll('SELECT team_id FROM rbac_team_members WHERE user_id = ?', ['5']);

        $this->assertCount(2, $rows);
        $this->assertSame('sales_team', $rows[1]['team_id']);
    }

    /**
     * @test
     * @since 1.0.0
     */
    public function testFetchAllReturnsEmptyWhenQueryFails(): void
    {
        AdapterState::$queryReturnsNull = true;

        $adapter = new FaDbAdapter('0_');
        $rows    = $adapter->fetchAll('SELECT team_id FROM rbac_team_members WHERE user_id = ?', ['5']);

        $this->assertSame([], $rows);
    }

    /**
     * @test
     * @since 1.0.0
     */
    public function testFetchAllReturnsEmptyWhenNoRows(): void
    {
        AdapterState::$queryResults['__default__'] = [];

        $adapter = new FaDbAdapter('0_');
        $rows    = $adapter->fetchAll('SELECT team_id FROM rbac_team_members WHERE user_id = ?', ['5']);

        $this->assertSame([], $rows);
    }

    // -------------------------------------------------------------------------
    // executeUpdate / lastInsertId
    // -------------------------------------------------------------------------

    /**
     * @test
     * @since 1.0.0
     */
    public function testExecuteUpdateReturnsAffectedRows(): void
    {
        AdapterState::$affectedRows = 3;

        $adapter = new FaDbAdapter('0_');
        $affected = $adapter->executeUpdate(
            'UPDATE rbac_teams SET inactive = 1 WHERE id = ?',
            ['sales_team']
        );

        $this->assertSame(3, $affected);
        $this->assertStringContainsString('0_rbac_teams', AdapterState::$lastQuery);
    }

    /**
     * @test
     * @since 1.0.0
     */
    public function testLastInsertIdReturnsInsertId(): void
    {
        AdapterState::$insertId = 99;

        $adapter = new FaDbAdapter('0_');
        $this->assertSame(99, $adapter->lastInsertId());
    }
}
