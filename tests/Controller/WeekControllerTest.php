<?php

namespace App\Tests\Controller;

use App\Entity\Account;
use App\Entity\Week;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class WeekControllerTest extends WebTestCase
{
    private const BASE = '/api/weeks';

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

    public function testIndexRequiresAuthentication(): void
    {
        $this->jsonRequest('GET', self::BASE);

        self::assertResponseStatusCodeSame(401);
    }

    public function testCreateListShowUpdateAndDeleteWeek(): void
    {
        $token = $this->loginToken();

        $created = $this->jsonRequest('POST', self::BASE, [
            'year' => 2026,
            'weekNumber' => 48,
            'startDate' => '2026-11-23',
        ], $token);

        self::assertResponseStatusCodeSame(201);
        self::assertSame(2026, $created['year']);
        self::assertSame(48, $created['weekNumber']);
        self::assertSame('2026-11-23', $created['startDate']);
        self::assertArrayHasKey('id', $created);

        $id = $created['id'];
        $listed = $this->jsonRequest('GET', self::BASE, token: $token);
        self::assertResponseStatusCodeSame(200);
        self::assertCount(1, $listed);

        $shown = $this->jsonRequest('GET', self::BASE . '/' . $id, token: $token);
        self::assertResponseStatusCodeSame(200);
        self::assertSame($id, $shown['id']);

        $updated = $this->jsonRequest('PUT', self::BASE . '/' . $id, [
            'year' => 2026,
            'weekNumber' => 49,
            'startDate' => '2026-11-30',
        ], $token);

        self::assertResponseStatusCodeSame(200);
        self::assertSame(49, $updated['weekNumber']);

        $this->jsonRequest('DELETE', self::BASE . '/' . $id, token: $token);
        self::assertResponseStatusCodeSame(204);

        $this->jsonRequest('GET', self::BASE . '/' . $id, token: $token);
        self::assertResponseStatusCodeSame(404);
    }

    public function testCreateRejectsDuplicateYearAndWeek(): void
    {
        $token = $this->loginToken();
        $payload = [
            'year' => 2026,
            'weekNumber' => 48,
            'startDate' => '2026-11-23',
        ];

        $this->jsonRequest('POST', self::BASE, $payload, $token);
        self::assertResponseStatusCodeSame(201);

        $this->jsonRequest('POST', self::BASE, $payload, $token);
        self::assertResponseStatusCodeSame(409);
    }

    public function testCreateRejectsInvalidPayload(): void
    {
        $this->jsonRequest('POST', self::BASE, [
            'year' => 1999,
            'weekNumber' => 54,
            'startDate' => '23-11-2026',
        ], $this->loginToken());

        self::assertResponseStatusCodeSame(422);
    }

    public function testCreateRejectsStartDateThatIsNotAMonday(): void
    {
        // 2026-11-24 est un mardi : le DTO l'accepte (format valide), le service le refuse
        $this->jsonRequest('POST', self::BASE, [
            'year' => 2026,
            'weekNumber' => 48,
            'startDate' => '2026-11-24',
        ], $this->loginToken());

        self::assertResponseStatusCodeSame(422);
    }

    public function testCreateRejectsOverlappingWeek(): void
    {
        // Semaine historique à une date non ISO : elle couvre du 2026-11-25 au 2026-12-02,
        // donc elle chevauche la S48 normale (2026-11-23 -> 2026-11-30)
        $existing = new Week();
        $existing->setYear(2026);
        $existing->setWeekNumber(47);
        $existing->setStartDate(new \DateTimeImmutable('2026-11-25'));
        $this->em->persist($existing);
        $this->em->flush();

        $this->jsonRequest('POST', self::BASE, [
            'year' => 2026,
            'weekNumber' => 48,
            'startDate' => '2026-11-23',
        ], $this->loginToken());

        self::assertResponseStatusCodeSame(409);
    }

    public function testShowMalformedAndUnknownIdReturn404(): void
    {
        $token = $this->loginToken();

        $this->jsonRequest('GET', self::BASE . '/not-a-uuid', token: $token);
        self::assertResponseStatusCodeSame(404);

        $this->jsonRequest('GET', self::BASE . '/018f0000-0000-7000-8000-000000000000', token: $token);
        self::assertResponseStatusCodeSame(404);
    }

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
        $credentials = ['email' => 'week-admin@example.com', 'password' => 'secret123'];

        $this->jsonRequest('POST', '/auth/register', $credentials);

        return $this->jsonRequest('POST', '/auth/login', $credentials)['token'];
    }

    public function testDeleteWeekCascadesToItsShifts(): void
    {
        $token = $this->loginToken();

        $created = $this->jsonRequest('POST', self::BASE, [
            'year' => 2026,
            'weekNumber' => 48,
            'startDate' => '2026-11-23',
        ], $token);
        $weekId = $created['id'];

        $shift = $this->jsonRequest('POST', '/api/shifts', [
            'weekId' => $weekId,
            'date' => '2026-11-23',
            'startTime' => '11:30',
            'endTime' => '15:00',
            'skillsRequired' => ['caisse'],
        ], $token);
        self::assertResponseStatusCodeSame(201);

        $this->jsonRequest('DELETE', self::BASE . '/' . $weekId, token: $token);
        self::assertResponseStatusCodeSame(204);

        // La semaine et son shift ont bien été supprimés
        $this->jsonRequest('GET', self::BASE . '/' . $weekId, token: $token);
        self::assertResponseStatusCodeSame(404);

        $this->jsonRequest('GET', '/api/shifts/' . $shift['id'], token: $token);
        self::assertResponseStatusCodeSame(404);
    }

    public function testUpdateRejectsChangingPeriodWhenWeekHasShifts(): void
    {
        $token = $this->loginToken();

        $created = $this->jsonRequest('POST', self::BASE, [
            'year' => 2026,
            'weekNumber' => 48,
            'startDate' => '2026-11-23',
        ], $token);
        $weekId = $created['id'];

        $this->jsonRequest('POST', '/api/shifts', [
            'weekId' => $weekId,
            'date' => '2026-11-23',
            'startTime' => '11:30',
            'endTime' => '15:00',
        ], $token);
        self::assertResponseStatusCodeSame(201);

        // Tentative de décaler la semaine à S49 alors qu'elle a un shift
        $this->jsonRequest('PUT', self::BASE . '/' . $weekId, [
            'year' => 2026,
            'weekNumber' => 49,
            'startDate' => '2026-11-30',
        ], $token);

        self::assertResponseStatusCodeSame(409);
    }
}
