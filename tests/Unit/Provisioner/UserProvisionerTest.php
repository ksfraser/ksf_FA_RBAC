<?php

declare(strict_types=1);

namespace Ksfraser\Tests\FA\Rbac\Unit\Provisioner;

use PHPUnit\Framework\TestCase;
use Ksfraser\FA\Rbac\Provisioner\UserProvisioner;
use Ksfraser\FA\Rbac\Contract\DbAdapterInterface;

/**
 * Unit tests for UserProvisioner.
 *
 * @covers \Ksfraser\FA\Rbac\Provisioner\UserProvisioner
 * @since 1.0.0
 */
class UserProvisionerTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * @param array $contactRow  Row for crm_contacts lookup (null = not found)
     * @param array $calls       Collector for executeUpdate / lastInsertId calls
     * @return DbAdapterInterface&\PHPUnit\Framework\MockObject\MockObject
     */
    private function makeDb(?array $contactRow, array &$calls): DbAdapterInterface
    {
        $db = $this->createMock(DbAdapterInterface::class);

        // fetchAssoc: first call = crm_contacts lookup, second = team exists check
        $db->method('fetchAssoc')
            ->willReturnCallback(function (string $sql, array $params) use ($contactRow, &$calls) {
                $calls[] = ['fetchAssoc', $sql, $params];
                if (strpos($sql, 'crm_contacts') !== false && strpos($sql, "type = 'user'") !== false) {
                    return $contactRow;
                }
                // team exists check
                if (strpos($sql, 'rbac_teams') !== false) {
                    return null; // team does not exist
                }
                return null;
            });

        $insertIdSeq = [10, 20, 100]; // person_id, contact_id, team_member_id
        $insertIdx   = 0;
        $db->method('executeUpdate')
            ->willReturnCallback(function (string $sql, array $params) use (&$calls) {
                $calls[] = ['executeUpdate', $sql, $params];
                return 1;
            });

        $db->method('lastInsertId')
            ->willReturnCallback(function () use (&$insertIdx, $insertIdSeq) {
                $id = $insertIdSeq[$insertIdx] ?? 99;
                $insertIdx++;
                return $id;
            });

        return $db;
    }

    // -------------------------------------------------------------------------
    // Tests
    // -------------------------------------------------------------------------

    /**
     * @test
     * @since 1.0.0
     */
    public function testProvisionCreatesPersonContactAndTeamWhenNoneExist(): void
    {
        $calls = [];
        $db    = $this->makeDb(null, $calls);

        $provisioner = new UserProvisioner($db);
        $contactId   = $provisioner->provision(5, 'jdoe', 'John Doe', 'jdoe@example.com');

        // Should have returned the new contact_id (second lastInsertId call = 20)
        $this->assertSame(20, $contactId);

        // Should have inserted: crm_persons, crm_contacts, rbac_teams, rbac_team_members
        $updateSqls = array_column(
            array_filter($calls, fn($c) => $c[0] === 'executeUpdate'),
            1
        );

        $this->assertCount(4, $updateSqls, 'Expected 4 INSERT statements');

        $this->assertStringContainsString('crm_persons', $updateSqls[0]);
        $this->assertStringContainsString('crm_contacts', $updateSqls[1]);
        $this->assertStringContainsString('rbac_teams', $updateSqls[2]);
        $this->assertStringContainsString('rbac_team_members', $updateSqls[3]);
    }

    /**
     * @test
     * @since 1.0.0
     */
    public function testProvisionIsIdempotentWhenContactAlreadyExists(): void
    {
        $calls      = [];
        $existingRow = ['id' => 7, 'person_id' => 3];
        $db         = $this->makeDb($existingRow, $calls);

        // Override team check to return existing team
        $db = $this->createMock(DbAdapterInterface::class);
        $db->method('fetchAssoc')
            ->willReturnCallback(function (string $sql, array $params) use ($existingRow) {
                if (strpos($sql, 'crm_contacts') !== false) {
                    return $existingRow;
                }
                if (strpos($sql, 'rbac_teams') !== false) {
                    return ['id' => '5_individual']; // team already exists
                }
                return null;
            });
        $db->expects($this->never())->method('executeUpdate');

        $provisioner = new UserProvisioner($db);
        $contactId   = $provisioner->provision(5, 'jdoe', 'John Doe', 'jdoe@example.com');

        $this->assertSame(7, $contactId);
    }

    /**
     * @test
     * @since 1.0.0
     */
    public function testProvisionCreatesOnlyTeamWhenContactExistsButTeamDoesNot(): void
    {
        $calls       = [];
        $existingRow = ['id' => 7, 'person_id' => 3];

        $db = $this->createMock(DbAdapterInterface::class);

        $seq = 0;
        $db->method('fetchAssoc')
            ->willReturnCallback(function (string $sql) use ($existingRow, &$seq) {
                $seq++;
                if (strpos($sql, 'crm_contacts') !== false) {
                    return $existingRow;   // contact found
                }
                if (strpos($sql, 'rbac_teams') !== false) {
                    return null;           // team NOT found
                }
                return null;
            });

        $insertSqls = [];
        $db->method('executeUpdate')
            ->willReturnCallback(function (string $sql, array $params) use (&$insertSqls) {
                $insertSqls[] = $sql;
                return 1;
            });
        $db->method('lastInsertId')->willReturn(99);

        $provisioner = new UserProvisioner($db);
        $contactId   = $provisioner->provision(5, 'jdoe', 'John Doe', 'jdoe@example.com');

        // Only rbac_teams and rbac_team_members should be inserted
        $this->assertCount(2, $insertSqls);
        $this->assertStringContainsString('rbac_teams', $insertSqls[0]);
        $this->assertStringContainsString('rbac_team_members', $insertSqls[1]);

        $this->assertSame(7, $contactId);
    }

    /**
     * @test
     * @since 1.0.0
     */
    public function testProvisionUsesCorrectIndividualTeamId(): void
    {
        $calls = [];
        $db    = $this->makeDb(null, $calls);

        $provisioner = new UserProvisioner($db);
        $provisioner->provision(42, 'alice', 'Alice Smith', 'alice@example.com');

        $teamInserts = array_filter(
            array_filter($calls, fn($c) => $c[0] === 'executeUpdate'),
            fn($c) => strpos($c[1], 'rbac_teams') !== false && strpos($c[1], 'INSERT') !== false
        );

        $teamInsert = array_values($teamInserts)[0] ?? null;
        $this->assertNotNull($teamInsert, 'Should INSERT into rbac_teams');
        // Team ID should be '42_individual'
        $this->assertContains('42_individual', $teamInsert[2], 'Team ID must be {userId}_individual');
    }
}
