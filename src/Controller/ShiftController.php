<?php

namespace App\Controller;

use App\Dto\ShiftOutput;
use App\Service\Contract\ShiftServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/shifts')]
final class ShiftController extends AbstractController
{
    public function __construct(private readonly ShiftServiceInterface $shifts)
    {
    }

    #[Route('', name: 'api_shift_index', methods: ['GET'])]
    public function index(#[MapQueryParameter] ?string $weekId = null): JsonResponse
    {
        $shifts = $weekId === null
            ? $this->shifts->findAll()
            : $this->shifts->findByWeek($weekId);

        return $this->json(array_map(ShiftOutput::toArray(...), $shifts));
    }

    #[Route('/{id}', name: 'api_shift_show', requirements: ['id' => Requirement::UUID], methods: ['GET'])]
    public function show(string $id): JsonResponse
    {
        return $this->json(ShiftOutput::toArray($this->shifts->findById($id)));
    }
}