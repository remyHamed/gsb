<?php

namespace App\Tests\Security;

use App\Entity\Account;
use App\Security\UserChecker;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;

final class UserCheckerTest extends TestCase
{
    private function account(bool $active): Account
    {
        return (new Account())
            ->setEmail('jean@example.com')
            ->setPasswordHash('hash')
            ->setIsActive($active);
    }

    public function testActiveAccountPasses(): void
    {
        (new UserChecker())->checkPreAuth($this->account(true));

        $this->addToAssertionCount(1); // aucune exception
    }

    public function testInactiveAccountIsRejected(): void
    {
        $this->expectException(CustomUserMessageAccountStatusException::class);

        (new UserChecker())->checkPreAuth($this->account(false));
    }
}
