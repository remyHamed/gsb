<?php

namespace App\Tests\Controller;

use App\Entity\Account;
use App\Entity\Employee;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class EmployeeControllerTest extends WebTestCase
{
    private const BASE = '/api/employees';

    private KernelBrowser $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = static::getContainer()->get('doctrine')->getManager();

        $this->em->createQuery('DELETE FROM ' . Employee::class)->execute();
        $this->em->createQuery('DELETE FROM ' . Account::class)->execute();
    }

    private function seedEmployee(string $email, string $firstName, bool $active = true): void
    {
        $account = (new Account())->setEmail($email)->setPasswordHash('not-used');
        $this->em->persist($account);

        $employee = (new Employee())
            ->setFirstName($firstName)
            ->setLastName('Test')
            ->setDateAnciennete(new \DateTimeImmutable('2024-01-01'))
            ->setWeeklyHours(35)
            ->setIsActive($active)
            ->setAccount($account);
        $this->em->persist($employee);
        $this->em->flush();
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
        $credentials = ['email' => 'admin@example.com', 'password' => 'secret123'];

        $this->jsonRequest('POST', '/auth/register', $credentials);

        return $this->jsonRequest('POST', '/auth/login', $credentials)['token'];
    }

    // ---------- GET /employees ----------

    public function testIndexRequiresAuthentication(): void
    {
        $this->jsonRequest('GET', self::BASE);

        self::assertResponseStatusCodeSame(401);
    }

    public function testIndexReturnsAllEmployees(): void
    {
        $this->seedEmployee('alice@example.com', 'Alice', true);
        $this->seedEmployee('bob@example.com', 'Bob', false);

        $data = $this->jsonRequest('GET', self::BASE, token: $this->loginToken());

        self::assertResponseStatusCodeSame(200);
        self::assertCount(2, $data);
    }

    public function testIndexExposesFullEmployeeShape(): void
    {
        $account = (new Account())->setEmail('alice@example.com')->setPasswordHash('x');
        $this->em->persist($account);

        $employee = (new Employee())
            ->setFirstName('Alice')
            ->setLastName('Martin')
            ->setDateAnciennete(new \DateTimeImmutable('2024-01-15'))
            ->setWeeklyHours(35)
            ->setAvailableSlots([1, 2, 3])
            ->setDatesExclues(['2026-12-25'])
            ->setSkills(['caisse'])
            ->setAccount($account);
        $this->em->persist($employee);
        $this->em->flush();

        $data = $this->jsonRequest('GET', self::BASE, token: $this->loginToken());
        $item = $data[0];

        self::assertSame('Alice', $item['firstName']);
        self::assertSame('Martin', $item['lastName']);
        self::assertSame('2024-01-15', $item['dateAnciennete']);
        self::assertSame(35, $item['weeklyHours']);
        self::assertSame([1, 2, 3], $item['availableSlots']);
        self::assertSame(['2026-12-25'], $item['datesExclues']);
        self::assertSame(['caisse'], $item['skills']);
        self::assertTrue($item['isActive']);
        self::assertSame($account->getId()->toRfc4122(), $item['accountId']);
        self::assertArrayHasKey('id', $item);
        self::assertArrayHasKey('createdAt', $item);
        self::assertArrayNotHasKey('passwordHash', $item);
        self::assertArrayNotHasKey('account', $item);
    }

    public function testIndexReturnsEmptyArrayWhenNoEmployee(): void
    {
        $data = $this->jsonRequest('GET', self::BASE, token: $this->loginToken());

        self::assertResponseStatusCodeSame(200);
        self::assertSame([], $data);
    }

    // ---------- GET /employees/active ----------

    public function testActiveReturnsOnlyActiveEmployees(): void
    {
        $this->seedEmployee('alice@example.com', 'Alice', true);
        $this->seedEmployee('bob@example.com', 'Bob', false);

        $data = $this->jsonRequest('GET', self::BASE . '/active', token: $this->loginToken());

        self::assertResponseStatusCodeSame(200);
        self::assertCount(1, $data);
        self::assertSame('Alice', $data[0]['firstName']);
    }
}
