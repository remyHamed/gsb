<?php

namespace App\Tests\Entity;

use App\Entity\Account;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class AccountTest extends TestCase
{
    private function makeAccount(array $roles = ['EMPLOYEE']): Account
    {
        return (new Account())
            ->setEmail('jean@example.com')
            ->setPasswordHash('hashed_value')
            ->setRoles($roles);
    }

    public function testImplementsSecurityInterfaces(): void
    {
        $account = $this->makeAccount();

        self::assertInstanceOf(UserInterface::class, $account);
        self::assertInstanceOf(PasswordAuthenticatedUserInterface::class, $account);
    }

    public function testUserIdentifierIsEmail(): void
    {
        self::assertSame('jean@example.com', $this->makeAccount()->getUserIdentifier());
    }

    public function testGetPasswordReturnsPasswordHash(): void
    {
        self::assertSame('hashed_value', $this->makeAccount()->getPassword());
    }

    public function testRolesArePrefixedAndContainRoleUser(): void
    {
        $roles = $this->makeAccount(['ADMIN'])->getRoles();

        self::assertContains('ROLE_ADMIN', $roles);
        self::assertContains('ROLE_USER', $roles);
    }

    public function testRolesAreDeduplicated(): void
    {
        $roles = $this->makeAccount(['EMPLOYEE', 'ROLE_EMPLOYEE', 'EMPLOYEE'])->getRoles();

        self::assertSame(array_values(array_unique($roles)), $roles);
        self::assertCount(2, $roles); // ROLE_EMPLOYEE + ROLE_USER
    }

    public function testDefaultRoleIsEmployee(): void
    {
        self::assertContains('ROLE_EMPLOYEE', (new Account())->getRoles());
    }
}