<?php

namespace App\Dto\Auth;

use Symfony\Component\Validator\Constraints as Assert;

final class RegisterInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        public readonly ?string $email = null,

        #[Assert\NotBlank]
        #[Assert\Length(min: 8, minMessage: 'Le mot de passe doit contenir au moins {{ limit }} caractères.')]
        public readonly ?string $password = null,
    ) {
    }
}