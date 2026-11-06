<?php

namespace App\Controller;

use App\Dto\RegisterInput;
use App\Service\AuthService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/auth')]
final class AuthController extends AbstractController
{
    public function __construct(private readonly AuthService $authService)
    {
    }

    #[Route('/register', name: 'auth_register', methods: ['POST'])]
    public function register(#[MapRequestPayload] RegisterInput $input): JsonResponse
    {
        $account = $this->authService->register($input);

        return $this->json([
            'id' => (string) $account->getId(),
            'email' => $account->getEmail(),
            'roles' => $account->getRoles(),
            'isActive' => $account->isActive(),
        ], 201);
    }
}