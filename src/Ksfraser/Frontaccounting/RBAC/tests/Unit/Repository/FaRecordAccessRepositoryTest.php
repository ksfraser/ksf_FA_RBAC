<?php

declare(strict_types=1);

namespace Ksfraser\Tests\FA\Rbac\Unit\Repository;

use PHPUnit\Framework\TestCase;
use Ksfraser\FA\Rbac\Repository\FaRecordAccessRepository;
use Ksfraser\FA\Rbac\Contract\DbAdapterInterface;
use Ksfraser\Rbac\Entity\RecordAccess;
use Ksfraser\Rbac\ValueObject\CapabilitySet;
use Ksfraser\Rbac\ValueObject\ProjectionName;

/**
 * Unit tests for FaRecordAccessRepository.
 *
 * @covers \Ksfraser\FA\Rbac\Repository\FaRecordAccessRepository
 * @since 1.0.0
 */
class FaRecordAccessRepositoryTest extends TestCase
{
    // -------------------------------------------------------------------------
    // findForRecord
    // -------------------------------------------------------------------------

    /**
     * @test
     * @since 1.0.0
     */
    public function testFindForRecordReturnsEmptyArrayWhenNone(): void
    {
        $db = $this->createMock(DbAdapterInterface::class);
        $db->method('fetchAll')->willReturn([]);

        $repo   = new FaRecordAccessRepository($db);
        $result = $repo->findForRecord('calendar', 'entry', 1, ['5_individual']);

        $this->assertSame([], $result);
    }

    /**
     * @test
     * @since 1.0.0
     */
    public function testFindForRecordReturnsRecordAccessInstances(): void
    {
        $db = $this->createMock(DbAdapterInterface::class);
        $db->method('fetchAll')->willReturn([
            [
                'id'          => '1',
                'module'      => 'calendar',
                'record_type' => 'entry',
                'record_id'   => '42',
                'team_id'     => '5_individual',
                'projection'  => 'public',
                'can_view'    => '1',
                'can_edit'    => '1',
                'can_delete'  => '0',
                'can_export'  => '0',
                'can_print'   => '0',
                'can_invite'  => '1',
                'can_restore' => '0',
                'granted_by'  => 'admin',
                'granted_at'  => '2025-01-01 00:00:00',
                'expires_at'  => null,
                'inactive'    => '0',
            ],
        ]);

        $repo   = new FaRecordAccessRepository($db);
        $result = $repo->findForRecord('calendar', 'entry', 42, ['5_individual']);

        $this->assertCount(1, $result);
        $this->assertInstanceOf(RecordAccess::class, $result[0]);
        $this->assertSame('calendar', $result[0]->getModule());
        $this->assertSame(42, $result[0]->getRecordId());
        $this->assertTrue($result[0]->getCapabilities()->canView());
        $this->assertTrue($result[0]->getCapabilities()->canInvite());
        $this->assertFalse($result[0]->getCapabilities()->canDelete());
    }

    // -------------------------------------------------------------------------
    // save
    // -------------------------------------------------------------------------

    /**
     * @test
     * @since 1.0.0
     */
    public function testSaveCallsInsertIntoRbacRecordAccess(): void
    {
        $db = $this->createMock(DbAdapterInterface::class);
        $db->expects($this->once())
            ->method('executeUpdate')
            ->with($this->stringContains('rbac_record_access'));

        $repo   = new FaRecordAccessRepository($db);
        $access = new RecordAccess(
            'calendar',
            'entry',
            42,
            '5_individual',
            new ProjectionName('public'),
            CapabilitySet::all(),
            'admin'
        );
        $repo->save($access);
    }

    // -------------------------------------------------------------------------
    // deactivateForTeam
    // -------------------------------------------------------------------------

    /**
     * @test
     * @since 1.0.0
     */
    public function testDeactivateForTeamCallsUpdateOnRbacRecordAccess(): void
    {
        $db = $this->createMock(DbAdapterInterface::class);
        $db->expects($this->once())
            ->method('executeUpdate')
            ->with(
                $this->stringContains('rbac_record_access'),
                $this->containsEqual('5_individual')
            );

        $repo = new FaRecordAccessRepository($db);
        $repo->deactivateForTeam('calendar', 'entry', 42, '5_individual');
    }

    // -------------------------------------------------------------------------
    // buildAccessJoinSql
    // -------------------------------------------------------------------------

    /**
     * @test
     * @since 1.0.0
     */
    public function testBuildAccessJoinSqlReturnsNonEmptyString(): void
    {
        $db   = $this->createMock(DbAdapterInterface::class);
        $repo = new FaRecordAccessRepository($db);
        $sql  = $repo->buildAccessJoinSql('calendar', 'entry', 'e');

        $this->assertIsString($sql);
        $this->assertNotEmpty($sql);
    }

    /**
     * @test
     * @since 1.0.0
     */
    public function testBuildAccessJoinSqlContainsModuleAndRecordType(): void
    {
        $db   = $this->createMock(DbAdapterInterface::class);
        $repo = new FaRecordAccessRepository($db);
        $sql  = $repo->buildAccessJoinSql('calendar', 'entry', 'e');

        $this->assertStringContainsString('calendar', $sql);
        $this->assertStringContainsString('entry', $sql);
    }

    /**
     * @test
     * @since 1.0.0
     */
    public function testBuildAccessJoinSqlContainsRbacTables(): void
    {
        $db   = $this->createMock(DbAdapterInterface::class);
        $repo = new FaRecordAccessRepository($db);
        $sql  = $repo->buildAccessJoinSql('calendar', 'entry', 'e');

        $this->assertStringContainsString('rbac_record_access', $sql);
        $this->assertStringContainsString('rbac_team_members', $sql);
    }

    /**
     * @test
     * @since 1.0.0
     */
    public function testBuildAccessJoinSqlUsesProvidedTableAlias(): void
    {
        $db   = $this->createMock(DbAdapterInterface::class);
        $repo = new FaRecordAccessRepository($db);
        $sql  = $repo->buildAccessJoinSql('calendar', 'entry', 'cal');

        $this->assertStringContainsString('cal.id', $sql);
    }

    // -------------------------------------------------------------------------
    // reassign
    // -------------------------------------------------------------------------

    /**
     * @test
     * @since 1.0.0
     */
    public function testReassignCallsUpdateAndInsertForAffectedRows(): void
    {
        $db = $this->createMock(DbAdapterInterface::class);

        // Simulate 2 rows to reassign
        $db->method('fetchAll')->willReturn([
            ['id' => '1', 'module' => 'calendar', 'record_type' => 'entry', 'record_id' => '10',
             'projection' => 'public', 'can_view' => '1', 'can_edit' => '1', 'can_delete' => '0',
             'can_export' => '0', 'can_print' => '0', 'can_invite' => '0', 'can_restore' => '0',
             'granted_by' => 'admin', 'granted_at' => '2025-01-01 00:00:00',
             'expires_at' => null, 'inactive' => '0', 'team_id' => 'old_team'],
            ['id' => '2', 'module' => 'calendar', 'record_type' => 'entry', 'record_id' => '20',
             'projection' => 'public', 'can_view' => '1', 'can_edit' => '0', 'can_delete' => '0',
             'can_export' => '0', 'can_print' => '0', 'can_invite' => '0', 'can_restore' => '0',
             'granted_by' => 'admin', 'granted_at' => '2025-01-01 00:00:00',
             'expires_at' => null, 'inactive' => '0', 'team_id' => 'old_team'],
        ]);

        $updateCount = 0;
        $db->method('executeUpdate')->willReturnCallback(function () use (&$updateCount) {
            $updateCount++;
            return 1;
        });

        $repo  = new FaRecordAccessRepository($db);
        $count = $repo->reassign('old_team', 'new_team', 'admin', [], 'calendar', 'entry');

        $this->assertSame(2, $count);
        // 2 deactivates + 2 inserts = 4 calls
        $this->assertSame(4, $updateCount);
    }
}
