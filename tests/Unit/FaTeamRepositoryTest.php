<?php

use PHPUnit\Framework\TestCase;
use Ksfraser\FA\Rbac\Repository\FaTeamRepository;
use Ksfraser\FA\Rbac\Contract\TeamRepositoryInterface;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @covers \Ksfraser\FA\Rbac\Repository\FaTeamRepository
 */
class FaTeamRepositoryTest extends TestCase
{
    use ProphecyTrait;

    public function testFindByIdReturnsTeamObject()
    {
        $dbAdapterProphecy = $this->prophesize();

        $sql = "SELECT id, display_name, team_type, owner_id, auto_managed, requires_approval, inactive 
                FROM " . TB_PREF . "rbac_teams 
                WHERE id = ? AND inactive = 0";
        $dbAdapterProphecy
            ->fetchAssoc($sql, [/* teamId */])
            ->willReturn(['id' => 'test-team', 'display_name' => 'Test Team', 'team_type' => 'individual', 'owner_id' => null, 'auto_managed' => 0, 'requires_approval' => 0, 'inactive' => 0]);

        $prophecyAdapter = $dbAdapterProphecy->reveal();

        $repo = $this->getMockBuilder(FaTeamRepository::class)->setConstructorArgs([$prophecyAdapter])->getMock();

        $team = $repo->findById('test-team');
        $this->assertInstanceOf(\Ksfraser\FA\Rbac\Contract\TeamInterface::class, $team);
        $this->assertEquals('test-team', $team->getId());
    }

    public function testSaveUpdatesTeam()
    {
        $dbAdapterProphecy = $this->prophesize();

        $sql = "INSERT INTO ... VALUES (?, ?, ?, ?, ?, ?, ?) 
                ON DUPLICATE KEY UPDATE ...";
        $dbAdapterProphecy
            ->executeUpdate($sql, [/* params */])
            ->willReturn(1);

        $prophecyAdapter = $dbAdapterProphecy->reveal();

        $repo = $this->getMockBuilder(FaTeamRepository::class)->setConstructorArgs([$prophecyAdapter])->getMock();

        $teamMock = $this->prophesize(\Ksfraser\FA\Rbac\Contract\TeamInterface::class);
        $teamMock->getId()->willReturn('test-team');
        $teamMock->getDisplayName()->willReturn('Test Team');
        $teamMock->getTeamType()->willReturn('individual');
        $teamMock->getOwnerId()->willReturn(null);
        $teamMock->isAutoManaged()->willReturn(false);
        $teamMock->requiresApproval()->willReturn(false);
        $teamMock->isInactive()->willReturn(false);

        $repo->save($teamMock->reveal());
        $dbAdapterProphecy->executeUpdate()->shouldHaveBeenCalled();
    }
}