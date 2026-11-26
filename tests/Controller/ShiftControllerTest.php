<?php

namespace App\Tests\Controller;

use App\Entity\Shift;
use App\Entity\Week;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ShiftControllerTest extends WebTestCase
{
    private const BASE = '/api/shifts';

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

    private function seedShift(Week $week, string $date, string $start, string $end, array $skills = ['caisse']): Shift
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

    // ---------- GET /api/shifts ----------

    public function testIndexRequiresAuthentication(): void
    {
        $this->jsonRequest('GET', self::BASE);

        self::assertResponseStatusCodeSame(401);
    }

    public function testIndexReturnsEmptyArrayWhenNoShift(): void
    {
        $data = $this->jsonRequest('GET', self::BASE, token: $this->loginToken());

        self::assertResponseStatusCodeSame(200);
        self::assertSame([], $data);
    }

    public function testIndexReturnsShiftsSortedByDateThenStartTimeWithFullShape(): void
    {
        $week = $this->seedWeek(2026, 48, '2026-11-23');
        // Créés dans le désordre : le dîner avant le déjeuner, puis un jour plus tôt
        $this->seedShift($week, '2026-11-24', '19:00', '23:00', ['salle']);
        $this->seedShift($week, '2026-11-23', '19:00', '23:00');
        $this->seedShift($week, '2026-11-23', '11:30', '15:00');

        $data = $this->jsonRequest('GET', self::BASE, token: $this->loginToken());

        self::assertResponseStatusCodeSame(200);
        self::assertCount(3, $data);
        self::assertSame(['2026-11-23', '2026-11-23', '2026-11-24'], array_column($data, 'date'));
        self::assertSame(['11:30', '19:00', '19:00'], array_column($data, 'startTime'));

        $first = $data[0];
        self::assertSame('15:00', $first['endTime']);
        self::assertSame(['caisse'], $first['skillsRequired']);
        self::assertSame($week->getId()->toRfc4122(), $first['weekId']);
        self::assertSame([], $first['assignedEmployeeIds']);
        self::assertArrayHasKey('id', $first);
    }

    public function testIndexFiltersByWeekId(): void
    {
        $week48 = $this->seedWeek(2026, 48, '2026-11-23');
        $week49 = $this->seedWeek(2026, 49, '2026-11-30');
        $this->seedShift($week48, '2026-11-23', '11:30', '15:00');
        $this->seedShift($week48, '2026-11-23', '19:00', '23:00');
        $this->seedShift($week49, '2026-11-30', '11:30', '15:00');

        $weekId = $week48->getId()->toRfc4122();
        $data = $this->jsonRequest('GET', self::BASE . '?weekId=' . $weekId, token: $this->loginToken());

        self::assertResponseStatusCodeSame(200);
        self::assertCount(2, $data);
        self::assertSame([$weekId, $weekId], array_column($data, 'weekId'));
    }

    public function testIndexWithUnknownWeekIdReturns404(): void
    {
        $this->jsonRequest('GET', self::BASE . '?weekId=018f0000-0000-7000-8000-000000000000', token: $this->loginToken());

        self::assertResponseStatusCodeSame(404);
    }

    public function testIndexWithMalformedWeekIdReturns404(): void
    {
        $this->jsonRequest('GET', self::BASE . '?weekId=not-a-uuid', token: $this->loginToken());

        self::assertResponseStatusCodeSame(404);
    }

    public function testShowReturnsShiftWithFullShape(): void
    {
        $week = $this->seedWeek(2026, 48, '2026-11-23');
        $shift = $this->seedShift($week, '2026-11-23', '11:30', '15:00', ['caisse', 'salle']);

        $data = $this->jsonRequest('GET', self::BASE . '/' . $shift->getId()->toRfc4122(), token: $this->loginToken());

        self::assertResponseStatusCodeSame(200);
        self::assertSame($shift->getId()->toRfc4122(), $data['id']);
        self::assertSame('2026-11-23', $data['date']);
        self::assertSame(['caisse', 'salle'], $data['skillsRequired']);
        self::assertSame($week->getId()->toRfc4122(), $data['weekId']);
    }

    public function testShowUnknownIdReturns404(): void
    {
        $this->jsonRequest('GET', self::BASE . '/018f0000-0000-7000-8000-000000000000', token: $this->loginToken());

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
        $credentials = ['email' => 'shift-admin@example.com', 'password' => 'secret123'];

        $this->jsonRequest('POST', '/auth/register', $credentials);

        return $this->jsonRequest('POST', '/auth/login', $credentials)['token'];
    }

    public function testCreateRequiresAuthentication(): void
    {
        $this->jsonRequest('POST', self::BASE, [
            'weekId' => '018f0000-0000-7000-8000-000000000000',
            'date' => '2026-11-23',
            'startTime' => '11:30',
            'endTime' => '15:00',
            'skillsRequired' => ['caisse'],
        ]);

        self::assertResponseStatusCodeSame(401);
    }

    public function testCreatePersistsShiftAndReturns201WithFullShape(): void
    {
        $week = $this->seedWeek(2026, 48, '2026-11-23');
        $weekId = $week->getId()->toRfc4122();

        $data = $this->jsonRequest('POST', self::BASE, [
            'weekId' => $weekId,
            'date' => '2026-11-23',
            'startTime' => '11:30',
            'endTime' => '15:00',
            'skillsRequired' => ['caisse'],
        ], token: $this->loginToken());

        self::assertResponseStatusCodeSame(201);
        self::assertArrayHasKey('id', $data);
        self::assertSame($weekId, $data['weekId']);
        self::assertSame('2026-11-23', $data['date']);
        self::assertSame('11:30', $data['startTime']);
        self::assertSame('15:00', $data['endTime']);
        self::assertSame(['caisse'], $data['skillsRequired']);
        self::assertSame([], $data['assignedEmployeeIds']);
        self::assertResponseHeaderSame('Location', self::BASE . '/' . $data['id']);
    }

    public function testCreateWithInvalidInputFormatReturns422(): void
    {
        $this->jsonRequest('POST', self::BASE, [
            'weekId' => 'not-a-uuid',
            'date' => '23/11/2026',
            'startTime' => '25:99',
            'endTime' => '15:00',
            'skillsRequired' => [''],
        ], token: $this->loginToken());

        self::assertResponseStatusCodeSame(422);
    }

    public function testCreateWithUnknownWeekReturns404(): void
    {
        $this->jsonRequest('POST', self::BASE, [
            'weekId' => '018f0000-0000-7000-8000-000000000000',
            'date' => '2026-11-23',
            'startTime' => '11:30',
            'endTime' => '15:00',
        ], token: $this->loginToken());

        self::assertResponseStatusCodeSame(404);
    }

    public function testCreateWithDateOutsideWeekOrInvalidHoursReturns422(): void
    {
        $week = $this->seedWeek(2026, 48, '2026-11-23');
        $token = $this->loginToken();
        $weekId = $week->getId()->toRfc4122();

        // Date hors semaine
        $this->jsonRequest('POST', self::BASE, [
            'weekId' => $weekId,
            'date' => '2026-11-30',
            'startTime' => '11:30',
            'endTime' => '15:00',
        ], token: $token);
        self::assertResponseStatusCodeSame(422);

        // endTime <= startTime
        $this->jsonRequest('POST', self::BASE, [
            'weekId' => $weekId,
            'date' => '2026-11-23',
            'startTime' => '15:00',
            'endTime' => '11:30',
        ], token: $token);
        self::assertResponseStatusCodeSame(422);
    }

    public function testCreateWithOverlapOrExceededQuotasReturns409(): void
    {
        $week = $this->seedWeek(2026, 48, '2026-11-23');
        $this->seedShift($week, '2026-11-23', '09:00', '11:30');
        $this->seedShift($week, '2026-11-23', '12:00', '15:00');
        $token = $this->loginToken();
        $weekId = $week->getId()->toRfc4122();

        // Chevauchement horaire
        $this->jsonRequest('POST', self::BASE, [
            'weekId' => $weekId,
            'date' => '2026-11-23',
            'startTime' => '11:00',
            'endTime' => '13:00',
        ], token: $token);
        self::assertResponseStatusCodeSame(409);

        // Quota 2 shifts / jour atteint
        $this->jsonRequest('POST', self::BASE, [
            'weekId' => $weekId,
            'date' => '2026-11-23',
            'startTime' => '18:00',
            'endTime' => '22:00',
        ], token: $token);
        self::assertResponseStatusCodeSame(409);
    }
}