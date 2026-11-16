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

    private function seedEmployee(string $email, string $firstName, bool $active = true): Employee
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

        return $employee;
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

    // ---------- GET /employees/{id} ----------

    public function testShowRequiresAuthentication(): void
    {
        $this->jsonRequest('GET', self::BASE . '/018f0000-0000-7000-8000-000000000000');

        self::assertResponseStatusCodeSame(401);
    }

    public function testShowReturnsEmployeeWithFullShape(): void
    {
        $employee = $this->seedEmployee('alice@example.com', 'Alice');

        $data = $this->jsonRequest('GET', self::BASE . '/' . $employee->getId()->toRfc4122(), token: $this->loginToken());

        self::assertResponseStatusCodeSame(200);
        self::assertSame($employee->getId()->toRfc4122(), $data['id']);
        self::assertSame('Alice', $data['firstName']);
        self::assertSame(35, $data['weeklyHours']);
        self::assertSame($employee->getAccount()->getId()->toRfc4122(), $data['accountId']);
        self::assertTrue($data['isActive']);
    }

    public function testShowReturnsInactiveEmployeeToo(): void
    {
        $employee = $this->seedEmployee('bob@example.com', 'Bob', false);

        $data = $this->jsonRequest('GET', self::BASE . '/' . $employee->getId()->toRfc4122(), token: $this->loginToken());

        self::assertResponseStatusCodeSame(200);
        self::assertFalse($data['isActive']);
    }

    public function testShowUnknownIdReturns404(): void
    {
        $this->jsonRequest('GET', self::BASE . '/018f0000-0000-7000-8000-000000000000', token: $this->loginToken());

        self::assertResponseStatusCodeSame(404);
    }

    public function testShowMalformedIdReturns404(): void
    {
        $this->jsonRequest('GET', self::BASE . '/not-a-uuid', token: $this->loginToken());

        self::assertResponseStatusCodeSame(404);
    }
    public function testCreateRequiresAuthentication(): void
    {
        $this->jsonRequest('POST', self::BASE, [
            'firstName' => 'Coreli',
            'lastName' => 'Martin',
        ]);

        self::assertResponseStatusCodeSame(401);
    }

    public function testCreateEmployeeSuccessfully(): void
    {
        // We need an existing account to link the employee to
        $account = (new Account())->setEmail('coreli@example.com')->setPasswordHash('x');
        $this->em->persist($account);
        $this->em->flush();

        $payload = [
            'firstName' => 'Coreli',
            'lastName' => 'Martin',
            'dateAnciennete' => '2026-07-13',
            'weeklyHours' => 20,
            'joursDisponibles' => [1, 2, 3, 4, 5],
            'datesExclues' => [],
            'skills' => ['php', 'symfony'],
            'accountId' => $account->getId()->toRfc4122(),
        ];

        $data = $this->jsonRequest('POST', self::BASE, $payload, token: $this->loginToken());

        self::assertResponseStatusCodeSame(201);
        self::assertSame('Coreli', $data['firstName']);
        self::assertSame('Martin', $data['lastName']);
        self::assertSame('2026-07-13', $data['dateAnciennete']);
        self::assertSame(20, $data['weeklyHours']);
        self::assertSame($account->getId()->toRfc4122(), $data['accountId']);
    }

    // ---------- PUT /employees/{id} ----------

    public function testUpdateEmployeeSuccessfully(): void
    {
        $employee = $this->seedEmployee('update@example.com', 'OldName');
        $accountId = $employee->getAccount()->getId()->toRfc4122();

        $payload = [
            'firstName' => 'NewName',
            'lastName' => 'Updated',
            'dateAnciennete' => '2024-01-01',
            'weeklyHours' => 35,
            'joursDisponibles' => [1, 2],
            'datesExclues' => [],
            'skills' => ['testing'],
            'accountId' => $accountId,
        ];

        $data = $this->jsonRequest('PUT', self::BASE . '/' . $employee->getId()->toRfc4122(), $payload, token: $this->loginToken());

        self::assertResponseStatusCodeSame(200);
        self::assertSame('NewName', $data['firstName']);
        self::assertSame('Updated', $data['lastName']);
    }

    // ---------- DELETE /employees/{id} ----------

    public function testDeleteEmployeeSuccessfully(): void
    {
        $employee = $this->seedEmployee('delete@example.com', 'ToDelete');
        $employeeId = $employee->getId()->toRfc4122();

        $this->jsonRequest('DELETE', self::BASE . '/' . $employeeId, token: $this->loginToken());

        self::assertResponseStatusCodeSame(204);

        // Verify it's actually gone
        $this->jsonRequest('GET', self::BASE . '/' . $employeeId, token: $this->loginToken());
        self::assertResponseStatusCodeSame(404);
    }   
}
