<?php

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class EmailAlreadyUsedException extends ConflictHttpException
{
    public function __construct(string $email)
    {
        parent::__construct(sprintf('L\'email "%s" est déjà utilisé.', $email));
    }
}