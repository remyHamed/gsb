<?php

namespace App\Tests\Security;

use App\Entity\Account;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasher;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

final class PasswordHasherTest extends TestCase
{
    public function testAccountPasswordCanBeHashedAndVerified(): void
    {
        $factory = new PasswordHasherFactory([
            PasswordAuthenticatedUserInterface::class => ['algorithm' => 'auto'],
        ]);
        $hasher = new UserPasswordHasher($factory);

        $account = (new Account())->setEmail('jean@example.com');
        $account->setPasswordHash($hasher->hashPassword($account, 'secret123'));

        self::assertNotSame('secret123', $account->getPasswordHash());
        self::assertTrue($hasher->isPasswordValid($account, 'secret123'));
        self::assertFalse($hasher->isPasswordValid($account, 'wrong'));
    }
}