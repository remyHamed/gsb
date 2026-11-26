<?php

namespace App\Tests\Controller;

use App\Entity\Account;
use App\Entity\Employee;
use App\Entity\Shift;
use App\Entity\Week;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class SchedulingControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = static::getContainer()->get('doctrine')->getManager();

        $this->em->createQuery('DELETE FROM App\Entity\Shift')->execute();
        $this->em->createQuery('DELETE FROM App\Entity\Employee')->execute();
        $this->em->createQuery('DELETE FROM App\Entity\Week')->execute();
        $this->em->createQuery('DELETE FROM App\Entity\Account')->execute();
    }

    // ---------- Fixtures ----------

    private function seedWeek(int $year, int $weekNumber, string $startDate): Week
    {
        $week = (new Week())
            ->setYear($year)
            ->setWeekNumber($weekNumber)
            ->setStartDate(new \DateTimeImmutable($startDate));
        $this->em->persist($week);
        $this->em->flush();

        return $week;
    }

    private function seedShift(Week $week, string $date, string $start, string $end, array $skills = ['salle']): Shift
    {
        $shift = (new Shift())
            ->setDate(new \DateTimeImmutable($date))
            ->setStartTime($start)
            ->setEndTime($end)
            ->setSkillsRequired($skills)
            ->setWeek($week);
        $this->em->persist($shift);
        $this->em->flush();

        return $shift;
    }

    private function seedEmployee(
        string $email,
        string $firstName,
        string $dateAnciennete,
        array $skills = ['salle'],
        array $availableSlots = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14],
        int $weeklyHours = 35,
    ): Employee {
        $account = (new Account())
            ->setEmail($email)
            ->setPasswordHash('$2y$13$dummyhashforintegrationtest');
        $this->em->persist($account);

        $employee = (new Employee(
            $account,
            $firstName,
            'Dupont',
            new \DateTimeImmutable($dateAnciennete),
            $weeklyHours,
        ))
            ->setSkills($skills)
            ->setAvailableSlots($availableSlots);

        $this->em->persist($employee);
        $this->em->flush();

        return $employee;
    }

    // ---------- GET /api/shifts/{id}/eligible-employees ----------

    public function testEligibleEmployeesRequiresAuthentication(): void
    {
        $this->jsonRequest('GET', '/api/shifts/018f0000-0000-7000-8000-000000000000/eligible-employees');

        self::assertResponseStatusCodeSame(401);
    }

    public function testEligibleEmployeesReturnsOnlyEligibleSortedBySeniority(): void
    {
        $week = $this->seedWeek(2026, 48, '2026-11-23');
        $shift = $this->seedShift($week, '2026-11-23', '11:30', '15:00', ['salle']); // slot 1

        $junior = $this->seedEmployee('junior@example.com', 'Junior', '2025-02-01', ['salle'], [1]);
        $senior = $this->seedEmployee('senior@example.com', 'Senior', '2021-05-10', ['salle'], [1]);
        $this->seedEmployee('cook@example.com', 'Cook', '2020-01-01', ['cuisine'], [1]);

        $data = $this->jsonRequest(
            'GET',
            '/api/shifts/' . $shift->getId()->toRfc4122() . '/eligible-employees',
            token: $this->loginToken(),
        );

        self::assertResponseStatusCodeSame(200);
        self::assertCount(2, $data);
        self::assertSame(
            [$senior->getId()->toRfc4122(), $junior->getId()->toRfc4122()],
            array_column($data, 'id'),
        );
    }

    public function testEligibleEmployeesUnknownShiftReturns404(): void
    {
        $this->jsonRequest(
            'GET',
            '/api/shifts/018f0000-0000-7000-8000-000000000000/eligible-employees',
            token: $this->loginToken(),
        );

        self::assertResponseStatusCodeSame(404);
    }

    // ---------- POST /api/shifts/{id}/assign & DELETE /api/shifts/{id}/assign/{employeeId} ----------

    public function testAssignAndUnassignEmployeeWorkflow(): void
    {
        $week = $this->seedWeek(2026, 48, '2026-11-23');
        $shift = $this->seedShift($week, '2026-11-23', '11:30', '15:00', ['salle']);
        $eligible = $this->seedEmployee('ok@example.com', 'Alice', '2023-01-01', ['salle'], [1]);
        $ineligible = $this->seedEmployee('ko@example.com', 'Bob', '2023-01-01', ['cuisine'], [1]);

        $token = $this->loginToken();
        $shiftId = $shift->getId()->toRfc4122();
        $eligibleId = $eligible->getId()->toRfc4122();

        // Assignation valide -> 200
        $assigned = $this->jsonRequest(
            'POST',
            "/api/shifts/{$shiftId}/assign",
            ['employeeId' => $eligibleId],
            token: $token,
        );
        self::assertResponseStatusCodeSame(200);
        self::assertSame([$eligibleId], $assigned['assignedEmployeeIds']);

        // Employé non éligible -> 422
        $this->jsonRequest(
            'POST',
            "/api/shifts/{$shiftId}/assign",
            ['employeeId' => $ineligible->getId()->toRfc4122()],
            token: $token,
        );
        self::assertResponseStatusCodeSame(422);

        // Désassignation -> 200
        $unassigned = $this->jsonRequest(
            'DELETE',
            "/api/shifts/{$shiftId}/assign/{$eligibleId}",
            token: $token,
        );
        self::assertResponseStatusCodeSame(200);
        self::assertSame([], $unassigned['assignedEmployeeIds']);
    }

    // ---------- POST /api/weeks/{id}/generate-schedule ----------

    public function testGenerateWeekSchedulePopulatesAllWeekShiftsAndPersists(): void
    {
        $week = $this->seedWeek(2026, 48, '2026-11-23');
        $lunch = $this->seedShift($week, '2026-11-23', '11:30', '15:00', ['salle']); // slot 1
        $dinner = $this->seedShift($week, '2026-11-23', '18:00', '22:30', ['salle']); // slot 2

        $senior = $this->seedEmployee('senior@example.com', 'Senior', '2021-01-01', ['salle'], [1, 2]);
        $junior = $this->seedEmployee('junior@example.com', 'Junior', '2025-01-01', ['salle'], [1]);

        $weekId = $week->getId()->toRfc4122();
        $data = $this->jsonRequest(
            'POST',
            "/api/weeks/{$weekId}/generate-schedule?maxPerShift=1",
            token: $this->loginToken(),
        );

        self::assertResponseStatusCodeSame(200);
        self::assertSame($weekId, $data['id']);

        $this->em->clear();
        $reloadedLunch = $this->em->find(Shift::class, $lunch->getId());
        $reloadedDinner = $this->em->find(Shift::class, $dinner->getId());

        self::assertCount(1, $reloadedLunch->getAssignedEmployees());
        self::assertSame($senior->getId()->toRfc4122(), $reloadedLunch->getAssignedEmployees()->first()->getId()->toRfc4122());

        self::assertCount(1, $reloadedDinner->getAssignedEmployees());
        self::assertSame($senior->getId()->toRfc4122(), $reloadedDinner->getAssignedEmployees()->first()->getId()->toRfc4122());

        self::assertArrayHasKey('shifts', $data);
        self::assertCount(2, $data['shifts']);
        self::assertSame([$senior->getId()->toRfc4122()], $data['shifts'][0]['assignedEmployeeIds']);
        self::assertSame([$senior->getId()->toRfc4122()], $data['shifts'][1]['assignedEmployeeIds']);
    }

    public function testGenerateWeekScheduleUnknownWeekReturns404(): void
    {
        $this->jsonRequest(
            'POST',
            '/api/weeks/018f0000-0000-7000-8000-000000000000/generate-schedule',
            token: $this->loginToken(),
        );

        self::assertResponseStatusCodeSame(404);
    }

    // ---------- Helpers ----------

    private function jsonRequest(string $method, string $uri, ?array $payload = null, ?string $token = null): array
    {
        $server = ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'];
        if ($token !== null) {
            $server['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
        }

        $this->client->request(
            $method,
            $uri,
            server: $server,
            content: $payload === null ? null : json_encode($payload, JSON_THROW_ON_ERROR),
        );

        return json_decode($this->client->getResponse()->getContent() ?: '[]', true) ?? [];
    }

    private function loginToken(): string
    {
        $credentials = ['email' => 'scheduling-admin@example.com', 'password' => 'secret123'];

        $this->jsonRequest('POST', '/auth/register', $credentials);

        return $this->jsonRequest('POST', '/auth/login', $credentials)['token'];
    }

    // ---------- DELETE /api/weeks/{id}/schedule ----------

  public function testResetWeekScheduleRequiresAuthentication(): void
  {
      $this->jsonRequest('DELETE', '/api/weeks/018f0000-0000-7000-8000-000000000000/schedule');

      self::assertResponseStatusCodeSame(401);
  }

  public function testResetWeekScheduleClearsAllAssignmentsAndReturnsUpdatedWeek(): void
  {
      $week = $this->seedWeek(2026, 48, '2026-11-23');
      $lunch = $this->seedShift($week, '2026-11-23', '11:30', '15:00', ['salle']);
      $dinner = $this->seedShift($week, '2026-11-23', '19:00', '23:00', ['salle']);
      $alice = $this->seedEmployee('alice@example.com', 'Alice', '2022-01-01');

      $lunch->addAssignedEmployee($alice);
      $dinner->addAssignedEmployee($alice);
      $this->em->flush();

      $weekId = $week->getId()->toRfc4122();
      $data = $this->jsonRequest(
          'DELETE',
          "/api/weeks/{$weekId}/schedule",
          token: $this->loginToken(),
      );

      self::assertResponseStatusCodeSame(200);
      self::assertSame($weekId, $data['id']);
      self::assertCount(2, $data['shifts']);
      self::assertSame([], $data['shifts'][0]['assignedEmployeeIds']);
      self::assertSame([], $data['shifts'][1]['assignedEmployeeIds']);

      $this->em->clear();
      self::assertCount(0, $this->em->find(Shift::class, $lunch->getId())->getAssignedEmployees());
      self::assertCount(0, $this->em->find(Shift::class, $dinner->getId())->getAssignedEmployees());
  }

  public function testResetWeekScheduleUnknownWeekReturns404(): void
  {
      $this->jsonRequest(
          'DELETE',
          '/api/weeks/018f0000-0000-7000-8000-000000000000/schedule',
          token: $this->loginToken(),
      );

      self::assertResponseStatusCodeSame(404);
  }
}