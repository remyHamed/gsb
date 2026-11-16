<?php

namespace App\Controller;

use App\Dto\EmployeeInput;
use App\Dto\EmployeeOutput;
use App\Service\Contract\EmployeeServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/employees')]
final class EmployeeController extends AbstractController
{
    public function __construct(private readonly EmployeeServiceInterface $employees)
    {
    }

    #[Route('', name: 'api_employee_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return $this->json(array_map(EmployeeOutput::toArray(...), $this->employees->findAll()));
    }

    #[Route('/active', name: 'api_employee_active', methods: ['GET'])]
    public function active(): JsonResponse
    {
        return $this->json(array_map(EmployeeOutput::toArray(...), $this->employees->findActive()));
    }

    #[Route('/{id}', name: 'api_employee_show', methods: ['GET'])]
    public function show(string $id): JsonResponse
    {
        return $this->json(EmployeeOutput::toArray($this->employees->findById($id)));
    }

    #[Route('', name: 'api_employee_create', methods: ['POST'])]
    public function create(#[MapRequestPayload] EmployeeInput $input): JsonResponse
    {
        $employee = $this->employees->create($input);

        return $this->json(EmployeeOutput::toArray($employee), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_employee_update', methods: ['PUT'])]
    public function update(string $id, #[MapRequestPayload] EmployeeInput $input): JsonResponse
    {
        $employee = $this->employees->update($id, $input);

        return $this->json(EmployeeOutput::toArray($employee));
    }

    #[Route('/{id}', name: 'api_employee_delete', methods: ['DELETE'])]
    public function delete(string $id): JsonResponse
    {
        $this->employees->delete($id);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}