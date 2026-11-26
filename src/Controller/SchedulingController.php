<?php

namespace App\Controller;

use App\Dto\Employee\EmployeeOutput;
use App\Dto\Scheduling\AssignEmployeeInput;
use App\Dto\Shift\ShiftOutput;
use App\Dto\Week\WeekOutput;
use App\Service\Contract\Scheduling\SchedulingServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api')]
final class SchedulingController extends AbstractController
{
    public function __construct(
        private readonly SchedulingServiceInterface $scheduling,
    ) {
    }

    #[Route(
        '/shifts/{id}/eligible-employees',
        name: 'api_scheduling_eligible_employees',
        requirements: ['id' => Requirement::UUID],
        methods: ['GET'],
    )]
    public function eligibleEmployees(string $id): JsonResponse
    {
        $employees = $this->scheduling->getEligibleEmployeesForShift($id);

        return $this->json(array_map(EmployeeOutput::toArray(...), $employees));
    }

    #[Route(
        '/shifts/{id}/assign',
        name: 'api_scheduling_assign',
        requirements: ['id' => Requirement::UUID],
        methods: ['POST'],
    )]
    public function assign(string $id, #[MapRequestPayload] AssignEmployeeInput $input): JsonResponse
    {
        try {
            $shift = $this->scheduling->assignEmployee($id, $input->employeeId);
        } catch (\DomainException $e) {
            return $this->json(
                ['message' => $e->getMessage()],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        return $this->json(ShiftOutput::toArray($shift));
    }

    #[Route(
        '/shifts/{id}/assign/{employeeId}',
        name: 'api_scheduling_unassign',
        requirements: ['id' => Requirement::UUID, 'employeeId' => Requirement::UUID],
        methods: ['DELETE'],
    )]
    public function unassign(string $id, string $employeeId): JsonResponse
    {
        $shift = $this->scheduling->unassignEmployee($id, $employeeId);

        return $this->json(ShiftOutput::toArray($shift));
    }

    #[Route(
        '/weeks/{id}/generate-schedule',
        name: 'api_scheduling_generate_week',
        requirements: ['id' => Requirement::UUID],
        methods: ['POST'],
    )]
    public function generateWeek(string $id, #[MapQueryParameter] ?int $maxPerShift = null): JsonResponse
    {
        $week = $this->scheduling->generateWeekSchedule($id, $maxPerShift);

        return $this->json(WeekOutput::toArray($week));
    }
}