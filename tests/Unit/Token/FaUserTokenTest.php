<?php

namespace Ksfraser\Tests\FrontAccounting\Rbac\Unit\Token;

use Ksfraser\FrontAccounting\Rbac\Token\FaUserToken;
use PHPUnit\Framework\TestCase;

class FaUserTokenTest extends TestCase
{
    public function testConstructorWithNullUser(): void
    {
        $token = new FaUserToken(null);

        $this->assertSame(0, $token->getUserId());
        $this->assertSame([], $token->getRoles());
        $this->assertNull($token->getUser());
        $this->assertFalse($token->isAuthenticated());
    }

    public function testConstructorWithEmptyUser(): void
    {
        $token = new FaUserToken((object)[]);

        $this->assertSame(0, $token->getUserId());
        $this->assertSame([], $token->getRoles());
        $this->assertFalse($token->isAuthenticated());
    }

    public function testConstructorWithValidUser(): void
    {
        $user = (object)[
            'user' => 5,
            'access' => [
                'SA_CUSTOMER' => 15,
                'SA_SALESORDER' => 5,
            ],
        ];

        $token = new FaUserToken($user);

        $this->assertSame(5, $token->getUserId());
        $this->assertTrue($token->isAuthenticated());
    }

    public function testRolesExtractedFromAccessLevels(): void
    {
        $user = (object)[
            'user' => 1,
            'access' => [
                'SA_CUSTOMER' => 15,
                'SA_SALESORDER' => 5,
                'SA_SALES_TRANSACTION' => 3,
            ],
        ];

        $token = new FaUserToken($user);
        $roles = $token->getRoles();

        $this->assertContains('manager', $roles);
        $this->assertContains('clerk', $roles);
        $this->assertContains('admin', $roles);
    }

    public function testAdminRoleForUserId1(): void
    {
        $user = (object)[
            'user' => 1,
            'access' => [],
        ];

        $token = new FaUserToken($user);

        $this->assertContains('admin', $token->getRoles());
    }

    public function testSalesmanRoleWhenSalesmanSet(): void
    {
        $user = (object)[
            'user' => 10,
            'access' => [],
            'salesman' => 'John Doe',
        ];

        $token = new FaUserToken($user);

        $this->assertContains('salesman', $token->getRoles());
    }

    public function testAccessLevel99OrHigherBecomesAdmin(): void
    {
        $user = (object)[
            'user' => 5,
            'access' => [
                'SA_CUSTOMER' => 99,
                'SA_INVOICE' => 100,
            ],
        ];

        $token = new FaUserToken($user);

        $this->assertContains('admin', $token->getRoles());
    }

    public function testAccessLevel10To98BecomesManager(): void
    {
        $user = (object)[
            'user' => 5,
            'access' => [
                'SA_CUSTOMER' => 50,
            ],
        ];

        $token = new FaUserToken($user);

        $this->assertContains('manager', $token->getRoles());
    }

    public function testAccessLevel1To9BecomesClerk(): void
    {
        $user = (object)[
            'user' => 5,
            'access' => [
                'SA_CUSTOMER' => 5,
            ],
        ];

        $token = new FaUserToken($user);

        $this->assertContains('clerk', $token->getRoles());
    }

    public function testAccessLevel0ProducesNoRole(): void
    {
        $user = (object)[
            'user' => 5,
            'access' => [
                'SA_CUSTOMER' => 0,
            ],
        ];

        $token = new FaUserToken($user);

        $this->assertNotContains('viewer', $token->getRoles());
        $this->assertNotContains('clerk', $token->getRoles());
    }

    public function testHasRoleReturnsTrueWhenRolePresent(): void
    {
        $user = (object)[
            'user' => 1,
            'access' => [
                'SA_CUSTOMER' => 15,
            ],
        ];

        $token = new FaUserToken($user);

        $this->assertTrue($token->hasRole('manager'));
        $this->assertTrue($token->hasRole('admin'));
        $this->assertFalse($token->hasRole('nonexistent'));
    }

    public function testHasAnyRoleReturnsTrueWhenAnyRolePresent(): void
    {
        $user = (object)[
            'user' => 5,
            'access' => [
                'SA_CUSTOMER' => 15,
            ],
        ];

        $token = new FaUserToken($user);

        $this->assertTrue($token->hasAnyRole(['manager', 'viewer']));
        $this->assertFalse($token->hasAnyRole(['viewer']));
    }

    public function testRolesAreUnique(): void
    {
        $user = (object)[
            'user' => 1,
            'access' => [
                'SA_CUSTOMER' => 99,
            ],
        ];

        $token = new FaUserToken($user);

        $this->assertCount(1, array_keys($token->getRoles(), 'admin'));
    }

    public function testFromSessionReturnsNewInstanceWhenNoSession(): void
    {
        $token = FaUserToken::fromSession();

        $this->assertInstanceOf(FaUserToken::class, $token);
        $this->assertFalse($token->isAuthenticated());
    }
}