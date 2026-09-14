<?php

namespace Ksfraser\Tests\FrontAccounting\Rbac\Unit\Voter;

use Ksfraser\FrontAccounting\Rbac\Voter\AbstractVoter;
use PHPUnit\Framework\TestCase;

class TestCustomerVoter extends AbstractVoter
{
    protected const SUPPORTED_ACTIONS = ['view', 'edit', 'delete'];
    protected const SUPPORTED_CLASS = 'stdClass';
    protected const MODULE = 'customer';

    protected function doVote(string $action, $subject, array $roles, array $context): ?bool
    {
        if (in_array('admin', $roles, true)) {
            return true;
        }

        switch ($action) {
            case 'view':
                return in_array('viewer', $roles, true);
            case 'edit':
                return in_array('manager', $roles, true);
            case 'delete':
                return false;
            default:
                return null;
        }
    }
}

class AbstractVoterTest extends TestCase
{
    private TestCustomerVoter $voter;

    protected function setUp(): void
    {
        $this->voter = new TestCustomerVoter();
    }

    public function testSupportsReturnsTrueForSupportedAction(): void
    {
        $this->assertTrue($this->voter->supports('view', new \stdClass()));
        $this->assertTrue($this->voter->supports('edit', new \stdClass()));
        $this->assertTrue($this->voter->supports('delete', new \stdClass()));
    }

    public function testSupportsReturnsFalseForUnsupportedAction(): void
    {
        $this->assertFalse($this->voter->supports('create', new \stdClass()));
        $this->assertFalse($this->voter->supports('approve', new \stdClass()));
    }

    public function testSupportsReturnsFalseForWrongSubjectClass(): void
    {
        $this->assertFalse($this->voter->supports('view', 'string'));
        $this->assertFalse($this->voter->supports('view', 123));
    }

    public function testSupportsReturnsTrueForNullSubject(): void
    {
        $this->assertTrue($this->voter->supports('view', null));
    }

    public function testVoteOnAttributeAbstainsForUnsupportedCombination(): void
    {
        $result = $this->voter->voteOnAttribute('create', new \stdClass(), ['viewer'], []);
        $this->assertNull($result);
    }

    public function testVoteOnAttributeDelegatesToDoVote(): void
    {
        $result = $this->voter->voteOnAttribute('view', new \stdClass(), ['viewer'], []);
        $this->assertTrue($result);

        $result = $this->voter->voteOnAttribute('edit', new \stdClass(), ['manager'], []);
        $this->assertTrue($result);
    }

    public function testVoteOnAttributeReceivesContext(): void
    {
        $context = ['user_id' => 5, 'module' => 'customer'];
        $result = $this->voter->voteOnAttribute('view', new \stdClass(), ['viewer'], $context);
        $this->assertTrue($result);
    }

    public function testGetModuleReturnsConfiguredModule(): void
    {
        $this->assertSame('customer', $this->voter->getModule());
    }

    public function testHasPermissionReturnsTrueForAdmin(): void
    {
        $reflection = new \ReflectionClass(AbstractVoter::class);
        $method = $reflection->getMethod('hasPermission');
        $method->setAccessible(true);

        $result = $method->invoke($this->voter, ['admin', 'viewer'], 'anything');
        $this->assertTrue($result);
    }

    public function testHasPermissionChecksRoleMatchesPermission(): void
    {
        $reflection = new \ReflectionClass(AbstractVoter::class);
        $method = $reflection->getMethod('hasPermission');
        $method->setAccessible(true);

        $result = $method->invoke($this->voter, ['manager'], 'customer_manager');
        $this->assertTrue($result);

        $result = $method->invoke($this->voter, ['clerk'], 'customer_manager');
        $this->assertFalse($result);
    }

    public function testRoleHasPermissionForAdmin(): void
    {
        $reflection = new \ReflectionClass(AbstractVoter::class);
        $method = $reflection->getMethod('roleHasPermission');
        $method->setAccessible(true);

        $result = $method->invoke($this->voter, 'admin', 'any_permission');
        $this->assertTrue($result);
    }

    public function testRoleHasPermissionChecksPermissionContainsRole(): void
    {
        $reflection = new \ReflectionClass(AbstractVoter::class);
        $method = $reflection->getMethod('roleHasPermission');
        $method->setAccessible(true);

        $result = $method->invoke($this->voter, 'manager', 'customer_manager');
        $this->assertTrue($result);

        $result = $method->invoke($this->voter, 'manager', 'sales_order');
        $this->assertFalse($result);
    }
}