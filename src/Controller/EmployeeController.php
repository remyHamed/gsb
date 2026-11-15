<?php

namespace App\Controller;

use App\Dto\EmployeeInput;
use App\Dto\EmployeeOutput;
use App\Entity\Employee;
use App\Service\Contract\EmployeeServiceInterface;
use Doctrine\ORM\EntityManagerInterface;
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
    public function create(
        #[MapRequestPayload] EmployeeInput $input,
        EntityManagerInterface $em,
    ): JsonResponse {
        $employee = new Employee();
        $this->hydrate($employee, $input);
        $em->persist($employee);
        $em->flush();

        return $this->json(EmployeeOutput::toArray($employee), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_employee_update', methods: ['PUT'])]
    public function update(
        Employee $employee,
        #[MapRequestPayload] EmployeeInput $input,
        EntityManagerInterface $em,
    ): JsonResponse {
        $this->hydrate($employee, $input);
        $em->flush();

        return $this->json(EmployeeOutput::toArray($employee));
    }

    #[Route('/{id}', name: 'api_employee_delete', methods: ['DELETE'])]
    public function delete(Employee $employee, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($employee);
        $em->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    private function hydrate(Employee $employee, EmployeeInput $input): void
    {
        $employee->setFirstName($input->firstName);
        $employee->setLastName($input->lastName);
    }

}