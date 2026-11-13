<?php

namespace App\Service\Contract;

use App\Dto\RegisterInput;
use App\Entity\Account;

interface AuthServiceInterface
{
    public function register(RegisterInput $input): Account;
}