<?php

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class WeekNotFoundException extends NotFoundHttpException
{
    public function __construct(string $id)
    {
        parent::__construct(sprintf('Semaine "%s" introuvable.', $id));
    }
}
