<?php

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ShiftNotFoundException extends NotFoundHttpException
{
    public function __construct(string $id)
    {
        parent::__construct(sprintf('Shift "%s" introuvable.', $id));
    }
}
