<?php

namespace App\Controller;

use App\Dto\Week\WeekInput;
use App\Dto\Week\WeekOutput;
use App\Service\Contract\WeekServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/weeks')]
final class WeekController extends AbstractController
{
    public function __construct(private readonly WeekServiceInterface $weeks)
    {
    }

    #[Route('', name: 'api_week_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return $this->json(array_map(WeekOutput::toArray(...), $this->weeks->findAll()));
    }

    #[Route('/{id}', name: 'api_week_show', requirements: ['id' => Requirement::UUID], methods: ['GET'])]
    public function show(string $id): JsonResponse
    {
        return $this->json(WeekOutput::toArray($this->weeks->findById($id)));
    }

    #[Route('', name: 'api_week_create', methods: ['POST'])]
    public function create(#[MapRequestPayload] WeekInput $input): JsonResponse
    {
        return $this->json(
            WeekOutput::toArray($this->weeks->create($input)),
            Response::HTTP_CREATED
        );
    }

    #[Route('/{id}', name: 'api_week_update', requirements: ['id' => Requirement::UUID], methods: ['PUT'])]
    public function update(string $id, #[MapRequestPayload] WeekInput $input): JsonResponse
    {
        return $this->json(WeekOutput::toArray($this->weeks->update($id, $input)));
    }

    #[Route('/{id}', name: 'api_week_delete', requirements: ['id' => Requirement::UUID], methods: ['DELETE'])]
    public function delete(string $id): JsonResponse
    {
        $this->weeks->delete($id);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
