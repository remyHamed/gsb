<?php

namespace App\Tests\Service;

use App\Dto\RegisterInput;
use App\Entity\Account;
use App\Exception\EmailAlreadyUsedException;
use App\Repository\Contract\AccountRepositoryInterface;
use App\Service\Impl\AuthService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AuthServiceTest extends TestCase
{
    public function testRegisterHashesPasswordAndPersists(): void
    {
        $repo = $this->createStub(AccountRepositoryInterface::class);
        $repo->method('findOneBy')->willReturn(null);

        $hasher = $this->createStub(UserPasswordHasherInterface::class);
        $hasher->method('hashPassword')->willReturn('hashed_value');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('persist')->with(self::isInstanceOf(Account::class));
        $em->expects(self::once())->method('flush');

        $account = (new AuthService($em, $repo, $hasher))
            ->register(new RegisterInput('jean@example.com', 'secret123'));

        self::assertSame('jean@example.com', $account->getEmail());
        self::assertSame('hashed_value', $account->getPasswordHash());
        self::assertContains('ROLE_EMPLOYEE', $account->getRoles());
    }

    public function testRegisterRejectsDuplicateEmail(): void
    {
        $repo = $this->createStub(AccountRepositoryInterface::class);
        $repo->method('findOneBy')->willReturn(new Account());

        $hasher = $this->createStub(UserPasswordHasherInterface::class);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::never())->method('persist');
        $em->expects(self::never())->method('flush');

        $this->expectException(EmailAlreadyUsedException::class);

        (new AuthService($em, $repo, $hasher))
            ->register(new RegisterInput('jean@example.com', 'secret123'));
    }
}