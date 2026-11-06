<?php

namespace App\Service;

use App\Dto\RegisterInput;
use App\Entity\Account;
use App\Exception\EmailAlreadyUsedException;
use App\Repository\AccountRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly AccountRepository $accounts,
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
            // deux inscriptions simultanées avec le même email
            throw new EmailAlreadyUsedException($input->email);
        }

        return $account;
    }
}