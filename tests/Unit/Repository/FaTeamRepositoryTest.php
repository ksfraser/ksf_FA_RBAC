<?php

declare(strict_types=1);

namespace Ksfraser\Tests\FA\Rbac\Unit\Repository;

use PHPUnit\Framework\TestCase;
use Ksfraser\FA\Rbac\Repository\FaTeamRepository;
use Ksfraser\FA\Rbac\Contract\DbAdapterInterface;
use Ksfraser\Rbac\Entity\Team;
use Ksfraser\Rbac\Entity\TeamMember;

/**
 * Unit tests for FaTeamRepository.
 *
 * @covers \Ksfraser\FA\Rbac\Repository\FaTeamRepository
 * @since 1.0.0
 */
class FaTeamRepositoryTest extends TestCase
{
    // -------------------------------------------------------------------------
    // findById
    // -------------------------------------------------------------------------

    /**
     * @test
     * @since 1.0.0
     */
    public function testFindByIdReturnsTeamWhenFound(): void
    {
        $db = $this->createMock(DbAdapterInterface::class);
        $db->method('fetchAssoc')->willReturn([
            'id'               => 'sales_team',
            'display_name'     => 'Sales Team',
            'team_type'        => Team::TYPE_EXPLICIT,
            'owner_id'         => 'alice',
            'auto_managed'     => '0',
            'requires_approval'=> '0',
            'inactive'         => '0',
            'created_at'       => '2025-01-01 00:00:00',
            'updated_at'       => '2025-01-01 00:00:00',
        ]);

        $repo = new FaTeamRepository($db);
        $team = $repo->findById('sales_team');

        $this->assertInstanceOf(Team::class, $team);
        $this->assertSame('sales_team', $team->getId());
        $this->assertSame('Sales Team', $team->getDisplayName());
        $this->assertSame(Team::TYPE_EXPLICIT, $team->getTeamType());
    }

    /**
     * @test
     * @since 1.0.0
     */
    public function testFindByIdReturnsNullWhenNotFound(): void
    {
        $db = $this->createMock(DbAdapterInterface::class);
        $db->method('fetchAssoc')->willReturn(null);

        $repo = new FaTeamRepository($db);
        $this->assertNull($repo->findById('no_such_team'));
    }

    // -------------------------------------------------------------------------
    // findDirectTeamIdsForUser / findEffectiveTeamIdsForUser
    // -------------------------------------------------------------------------

    /**
     * @test
     * @since 1.0.0
     */
    public function testFindDirectTeamIdsForUserReturnsArrayOfStrings(): void
    {
        $db = $this->createMock(DbAdapterInterface::class);
        $db->method('fetchAll')->willReturn([
            ['team_id' => '5_individual'],
            ['team_id' => 'sales_team'],
        ]);

        $repo = new FaTeamRepository($db);
        $ids  = $repo->findDirectTeamIdsForUser('5');

        $this->assertSame(['5_individual', 'sales_team'], $ids);
    }

    /**
     * @test
     * @since 1.0.0
     */
    public function testFindEffectiveTeamIdsForUserReturnsDedupedArray(): void
    {
        $db = $this->createMock(DbAdapterInterface::class);
        $db->method('fetchAll')->willReturn([
            ['team_id' => '5_individual'],
            ['team_id' => 'sales_team'],
            ['team_id' => '5_individual'], // duplicate
        ]);

        $repo = new FaTeamRepository($db);
        $ids  = $repo->findEffectiveTeamIdsForUser('5');

        $this->assertSame(['5_individual', 'sales_team'], array_values($ids));
    }

    // -------------------------------------------------------------------------
    // save
    // -------------------------------------------------------------------------

    /**
     * @test
     * @since 1.0.0
     */
    public function testSaveCallsInsertIntoRbacTeams(): void
    {
        $db = $this->createMock(DbAdapterInterface::class);
        $db->expects($this->once())
            ->method('executeUpdate')
            ->with($this->stringContains('rbac_teams'));

        $repo = new FaTeamRepository($db);
        $team = new Team('test_team', 'Test Team', Team::TYPE_EXPLICIT, 'alice');
        $repo->save($team);
    }

    // -------------------------------------------------------------------------
    // deactivate
    // -------------------------------------------------------------------------

    /**
     * @test
     * @since 1.0.0
     */
    public function testDeactivateCallsUpdateWithInactive(): void
    {
        $db = $this->createMock(DbAdapterInterface::class);
        $db->expects($this->once())
            ->method('executeUpdate')
            ->with(
                $this->stringContains('rbac_teams'),
                $this->containsEqual('test_team')
            );

        $repo = new FaTeamRepository($db);
        $repo->deactivate('test_team');
    }

    // -------------------------------------------------------------------------
    // exists
    // -------------------------------------------------------------------------

    /**
     * @test
     * @since 1.0.0
     */
    public function testExistsReturnsTrueWhenFound(): void
    {
        $db = $this->createMock(DbAdapterInterface::class);
        $db->method('fetchAssoc')->willReturn(['id' => 'foo']);

        $repo = new FaTeamRepository($db);
        $this->assertTrue($repo->exists('foo'));
    }

    /**
     * @test
     * @since 1.0.0
     */
    public function testExistsReturnsFalseWhenNotFound(): void
    {
        $db = $this->createMock(DbAdapterInterface::class);
        $db->method('fetchAssoc')->willReturn(null);

        $repo = new FaTeamRepository($db);
        $this->assertFalse($repo->exists('no_such_team'));
    }

    // -------------------------------------------------------------------------
    // addMember / approveMember / removeMember
    // -------------------------------------------------------------------------

    /**
     * @test
     * @since 1.0.0
     */
    public function testAddMemberCallsInsertIntoRbacTeamMembers(): void
    {
        $db = $this->createMock(DbAdapterInterface::class);
        $db->expects($this->once())
            ->method('executeUpdate')
            ->with($this->stringContains('rbac_team_members'));

        $repo   = new FaTeamRepository($db);
        $member = new TeamMember('sales_team', 'alice', 'admin');
        $repo->addMember($member);
    }

    /**
     * @test
     * @since 1.0.0
     */
    public function testApproveMemberCallsUpdateOnRbacTeamMembers(): void
    {
        $db = $this->createMock(DbAdapterInterface::class);
        $db->expects($this->once())
            ->method('executeUpdate')
            ->with(
                $this->stringContains('rbac_team_members'),
                $this->containsEqual('sales_team')
            );

        $repo = new FaTeamRepository($db);
        $repo->approveMember('sales_team', 'alice', 'admin');
    }

    /**
     * @test
     * @since 1.0.0
     */
    public function testRemoveMemberCallsUpdateSettingInactive(): void
    {
        $db = $this->createMock(DbAdapterInterface::class);
        $db->expects($this->once())
            ->method('executeUpdate')
            ->with(
                $this->stringContains('rbac_team_members'),
                $this->containsEqual('alice')
            );

        $repo = new FaTeamRepository($db);
        $repo->removeMember('sales_team', 'alice', 'admin');
    }
}
