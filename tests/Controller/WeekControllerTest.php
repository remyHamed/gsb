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
        $em = static::getContainer()->get('doctrine')->getManager();

        $em->createQuery('DELETE FROM App\Entity\Employee')->execute();
        $em->createQuery('DELETE FROM App\Entity\Week')->execute();
        $em->createQuery('DELETE FROM App\Entity\Account')->execute();
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
}
