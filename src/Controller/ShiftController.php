<?php

namespace App\Controller;

use App\Dto\Shift\ShiftOutput;
use App\Service\Contract\ShiftServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use App\Dto\Shift\ShiftInput;

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

    #[Route('', name: 'api_shift_create', methods: ['POST'])]
    public function create(#[MapRequestPayload] ShiftInput $input): JsonResponse
    {
        try {
            $shift = $this->shifts->create($input);
        } catch (\DomainException $e) {
            return $this->json(
                ['message' => $e->getMessage()],
                $this->resolveDomainExceptionStatus($e),
            );
        }

        $payload = ShiftOutput::toArray($shift);

        return $this->json(
            $payload,
            Response::HTTP_CREATED,
            ['Location' => '/api/shifts/' . $payload['id']],
        );
    }

    private function resolveDomainExceptionStatus(\DomainException $e): int
    {
        if (str_contains($e->getMessage(), 'overlaps') || str_contains($e->getMessage(), 'quota')) {
            return Response::HTTP_CONFLICT;
        }

        return Response::HTTP_UNPROCESSABLE_ENTITY;
    }
}