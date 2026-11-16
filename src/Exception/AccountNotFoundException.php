<?php
namespace App\Exception;

use Symfony\Component\HttpKernel\Attribute\WithHttpStatus;

#[WithHttpStatus(422)] // id dans le body inexistant = donnée invalide, pas une URL inconnue
final class AccountNotFoundException extends \DomainException
{
    public function __construct(string $id) { parent::__construct(sprintf('Account "%s" not found.', $id)); }
}