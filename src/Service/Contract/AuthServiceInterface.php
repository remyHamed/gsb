<?php

namespace App\Service\Contract;

use App\Dto\Auth\RegisterInput;
use App\Entity\Account;

interface AuthServiceInterface
{
    public function register(RegisterInput $input): Account;
}