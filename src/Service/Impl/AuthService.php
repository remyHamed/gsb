<?php

namespace App\Service\Impl;

use App\Entity\Account;
use App\Dto\RegisterInput;
use App\Exception\EmailAlreadyUsedException;
use App\Repository\Contract\AccountRepositoryInterface;
use App\Service\Contract\AuthServiceInterface;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthService implements AuthServiceInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly AccountRepositoryInterface $accounts,
        private readonly UserPasswordHasherInterface $hasher,
    ) {
    }

    public function register(RegisterInput $input): Account
    {
        if ($this->accounts->findOneBy(['email' => $input->email]) !== null) {
            throw new EmailAlreadyUsedException($input->email);
        }

        $account = (new Account())->setEmail($input->email);
        $account->setPasswordHash($this->hasher->hashPassword($account, $input->password));

        try {
            $this->em->persist($account);
            $this->em->flush();
        } catch (UniqueConstraintViolationException) {
            throw new EmailAlreadyUsedException($input->email);
        }

        return $account;
    }
}