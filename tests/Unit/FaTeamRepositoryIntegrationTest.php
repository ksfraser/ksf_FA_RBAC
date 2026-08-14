<?php

/**
 * @covers \Ksfraser\FA\Rbac\Repository\FaTeamRepository
 * @group Integration
 */
class FaTeamRepositoryIntegrationTest extends TestCase
{
    /** @test */
    public function test_findById_returns_valid_team_object()
    {
        $dbAdapter = $this->createMock(FaDbAdapter::class);
        $repo = new FaTeamRepository($dbAdapter);

        $team = $repo->findById('test-team-id');
        $this->assertInstanceOf(\Ksfraser\FA\Rbac\Contract\TeamInterface::class, $team);
        $this->assertEquals('test-team-id', $team->getId());
    }

    /** @test */
    public function test_save_updates_team_on_duplicate_key_update()
    {
        $dbAdapter = $this->createMock(FaDbAdapter::class);
        $repo = new FaTeamRepository($dbAdapter);

        $team = $this->prophesize(\Ksfraser\FA\Rbac\Contract\TeamInterface::class);
        $team->getId()->willReturn('updated-team');
        $team->getDisplayName()->willReturn('Updated Team');
        $team->getTeamType()->willReturn('individual');
        $team->getOwnerId()->willReturn(null);
        $team->isAutoManaged()->willReturn(false);
        $team->requiresApproval()->willReturn(false);
        $team->isInactive()->willReturn(false);

        $result = $repo->save($team);
        $this->assertTrue($result->isSuccessful());
    }

    /** @test */
    public function test_findEffectiveTeamIdsForUser_returns_empty_array_when_no_teams()
    {
        $dbAdapter = $this->createMock(FaDbAdapter::class);
        $repo = new FaTeamRepository($dbAdapter);

        $ids = $repo->findEffectiveTeamIdsForUser('user-without-teams');
        $this->assertEmpty($ids);
    }

    /** @test */
    public function test_findEffectiveTeamIdsForUser_returns_team_ids_when_user_has_teams()
    {
        $dbAdapter = $this->createMock(FaDbAdapter::class);
        $repo = new FaTeamRepository($dbAdapter);

        $team1 = $this->prophesize(\Ksfraser\FA\Rbac\Contract\TeamInterface::class);
        $team1->getId()->willReturn('team-a');
        $team1->getDisplayName()->willReturn('Team A');
        $team1->getTeamType()->willReturn('individual');
        $team1->getOwnerId()->willReturn(null);
        $team1->isAutoManaged()->willReturn(true);
        $team1->requiresApproval()->willReturn(true);
        $team1->isInactive()->willReturn(false);

        $team2 = $this->prophesize(\Ksfraser\FA\Rbac\Contract\TeamInterface::class);
        $team2->getId()->willReturn('team-b');
        $team2->getDisplayName()->willReturn('Team B');
        $team2->getTeamType()->willReturn('individual');
        $team2->getOwnerId()->willReturn(null);
        $team2->isAutoManaged()->willReturn(true);
        $team2->requiresApproval()->willReturn(true);
        $team2->isInactive()->willReturn(false);

        $ids = $repo->findEffectiveTeamIdsForUser('user-with-two-teams');
        $this->assertCount(2, $ids);
    }
}
