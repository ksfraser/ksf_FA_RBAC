<?php

namespace Ksfraser\Tests\FrontAccounting\Rbac\Unit;

use Ksfraser\FrontAccounting\Rbac\RbacService;
use Ksfraser\FrontAccounting\Rbac\Token\FaUserToken;
use Ksfraser\FrontAccounting\Rbac\Voter\AbstractVoter;
use PHPUnit\Framework\TestCase;

class TestVoter extends AbstractVoter
{
    protected const SUPPORTED_ACTIONS = ['view', 'edit'];
    protected const SUPPORTED_CLASS = null;
    protected const MODULE = 'test';

    protected function doVote(string $action, $subject, array $roles, array $context): ?bool
    {
        if (in_array('admin', $roles, true)) {
            return true;
        }

        if ($action === 'view' && in_array('viewer', $roles, true)) {
            return true;
        }

        if ($action === 'edit' && in_array('manager', $roles, true)) {
            return true;
        }

        return false;
    }
}

class RbacServiceTest extends TestCase
{
    private RbacService $service;

    protected function setUp(): void
    {
        $this->service = new RbacService();
    }

    public function testConstructorInitializesDefaultRoles(): void
    {
        $roles = $this->service->getRoles();

        $this->assertContains('admin', $roles);
        $this->assertContains('manager', $roles);
        $this->assertContains('salesman', $roles);
        $this->assertContains('clerk', $roles);
        $this->assertContains('ar_clerk', $roles);
        $this->assertContains('ap_clerk', $roles);
        $this->assertContains('warehouse', $roles);
        $this->assertContains('viewer', $roles);
    }

    public function testAddRoleCreatesNewRole(): void
    {
        $this->service->addRole('new_role');
        $this->assertContains('new_role', $this->service->getRoles());
    }

public function testAddRoleWithParents(): void
    {
        $this->service->addRole('new_role', ['manager']);
        $this->assertContains('new_role', $this->service->getRoles());
    }

    public function testAddPermissionAddsPermissionToRole(): void
    {
        $this->service->addPermission('test_permission', ['manager']);
        $this->assertTrue($this->service->isGranted('manager', 'test_permission'));
        $this->assertFalse($this->service->isGranted('clerk', 'test_permission'));
    }

    public function testIsGrantedReturnsFalseForNonexistentRole(): void
    {
        $this->assertFalse($this->service->isGranted('nonexistent', 'some_permission'));
    }

    public function testIsGrantedChecksPermission(): void
    {
        $this->service->addPermission('test_permission', ['manager']);
        $this->assertTrue($this->service->isGranted('manager', 'test_permission'));
    }

    public function testRegisterVoterAddsVoter(): void
    {
        $voter = new TestVoter();
        $this->service->registerVoter($voter);

        $reflection = new \ReflectionClass(RbacService::class);
        $property = $reflection->getProperty('voters');
        $property->setAccessible(true);

        $voters = $property->getValue($this->service);
        $this->assertCount(1, $voters);
    }

    public function testSetDecisionStrategyChangesStrategy(): void
    {
        $this->service->setDecisionStrategy(RbacService::STRATEGY_CONSENSUS);

        $reflection = new \ReflectionClass(RbacService::class);
        $property = $reflection->getProperty('strategy');
        $property->setAccessible(true);

        $this->assertSame(RbacService::STRATEGY_CONSENSUS, $property->getValue($this->service));
    }

    public function testSetDecisionStrategyIgnoresInvalidStrategy(): void
    {
        $this->service->setDecisionStrategy('invalid_strategy');

        $reflection = new \ReflectionClass(RbacService::class);
        $property = $reflection->getProperty('strategy');
        $property->setAccessible(true);

        $this->assertSame(RbacService::STRATEGY_AFFIRMATIVE, $property->getValue($this->service));
    }

    public function testAuthorizeWithNoVotersReturnsNull(): void
    {
        $user = new FaUserToken((object)['user' => 1, 'access' => []]);
        $result = $this->service->authorize('view', null, $user);
        $this->assertNull($result);
    }

    public function testAuthorizeWithAffirmativeStrategy(): void
    {
        $this->service->registerVoter(new TestVoter());

        $user = new FaUserToken((object)[
            'user' => 5,
            'access' => ['SA_CUSTOMER' => 15],
        ]);

        $this->assertTrue($this->service->authorize('edit', null, $user));
    }

    public function testAuthorizeWithConsensusStrategy(): void
    {
        $this->service->setDecisionStrategy(RbacService::STRATEGY_CONSENSUS);
        $this->service->registerVoter(new TestVoter());

        $user = new FaUserToken((object)[
            'user' => 5,
            'access' => ['SA_CUSTOMER' => 15],
        ]);

        $result = $this->service->authorize('edit', null, $user);
        $this->assertTrue($result);
    }

    public function testAuthorizeWithUnanimousStrategy(): void
    {
        $this->service->setDecisionStrategy(RbacService::STRATEGY_UNANIMOUS);
        $this->service->registerVoter(new TestVoter());

        $user = new FaUserToken((object)[
            'user' => 5,
            'access' => ['SA_CUSTOMER' => 15],
        ]);

        $result = $this->service->authorize('edit', null, $user);
        $this->assertTrue($result);
    }

    public function testAuthorizeReturnsFalseWhenNoVotersVote(): void
    {
        $this->service->registerVoter(new TestVoter());

        $user = new FaUserToken((object)[
            'user' => 5,
            'access' => [],
        ]);

        $this->assertFalse($this->service->authorize('edit', null, $user));
    }

    public function testRegisterModuleAcl(): void
    {
        $this->service->registerModuleAcl('customer', [
            'view' => ['viewer', 'clerk', 'manager', 'admin'],
            'edit' => ['manager', 'admin'],
        ]);

        $acl = $this->service->getModuleAcl('customer');
        $this->assertSame(['view', 'edit'], array_keys($acl));
    }

    public function testGetModuleAclReturnsEmptyForUnknownModule(): void
    {
        $acl = $this->service->getModuleAcl('nonexistent');
        $this->assertSame([], $acl);
    }

    public function testCanAccessChecksModuleAcl(): void
    {
        $this->service->registerModuleAcl('customer', [
            'view' => ['viewer', 'clerk', 'manager', 'admin'],
            'edit' => ['manager', 'admin'],
        ]);

        $this->assertTrue($this->service->canAccess('viewer', 'customer', 'view'));
        $this->assertTrue($this->service->canAccess('manager', 'customer', 'edit'));
        $this->assertFalse($this->service->canAccess('viewer', 'customer', 'edit'));
        $this->assertFalse($this->service->canAccess('clerk', 'customer', 'delete'));
    }

    public function testGetRbacReturnsZendRbacInstance(): void
    {
        $rbac = $this->service->getRbac();
        $this->assertInstanceOf(\Laminas\Permissions\Rbac\Rbac::class, $rbac);
    }

    public function testStrategyConstants(): void
    {
        $this->assertSame('affirmative', RbacService::STRATEGY_AFFIRMATIVE);
        $this->assertSame('consensus', RbacService::STRATEGY_CONSENSUS);
        $this->assertSame('unanimous', RbacService::STRATEGY_UNANIMOUS);
        $this->assertSame('priority', RbacService::STRATEGY_PRIORITY);
    }
}