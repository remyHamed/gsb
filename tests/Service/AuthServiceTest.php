<?php

namespace App\Tests\Service;

use App\Dto\Auth\RegisterInput;
use App\Entity\Account;
use App\Exception\EmailAlreadyUsedException;
use App\Repository\Contract\AccountRepositoryInterface;
use App\Service\Impl\AuthService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AuthServiceTest extends TestCase
{
    public function testRegisterHashesPasswordAndSaves(): void
    {
        $repo = $this->createMock(AccountRepositoryInterface::class);
        $repo->method('findOneByEmail')->willReturn(null);
        $repo->expects(self::once())
            ->method('save')
            ->with(self::isInstanceOf(Account::class), true);

        $hasher = $this->createStub(UserPasswordHasherInterface::class);
        $hasher->method('hashPassword')->willReturn('hashed_value');

        $account = (new AuthService($repo, $hasher))
            ->register(new RegisterInput('jean@example.com', 'secret123'));

        self::assertSame('jean@example.com', $account->getEmail());
        self::assertSame('hashed_value', $account->getPasswordHash());
        self::assertContains('ROLE_EMPLOYEE', $account->getRoles());
    }

    public function testRegisterRejectsDuplicateEmail(): void
    {
        $repo = $this->createMock(AccountRepositoryInterface::class);
        $repo->method('findOneByEmail')->willReturn(new Account());
        $repo->expects(self::never())->method('save');

        $hasher = $this->createStub(UserPasswordHasherInterface::class);

        $this->expectException(EmailAlreadyUsedException::class);

        (new AuthService($repo, $hasher))
            ->register(new RegisterInput('jean@example.com', 'secret123'));
    }
}
