<?php

namespace App\Tests\Controller;

use App\Entity\Account;
use App\Entity\Employee;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AuthControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = static::getContainer()->get('doctrine')->getManager();

        $this->em->createQuery('DELETE FROM ' . Employee::class)->execute();
        $this->em->createQuery('DELETE FROM ' . Account::class)->execute();
    }

    private function postJson(string $uri, array $payload): array
    {
        $this->client->request(
            'POST',
            $uri,
            server: ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
            content: json_encode($payload, JSON_THROW_ON_ERROR),
        );

        return json_decode($this->client->getResponse()->getContent() ?: '{}', true) ?? [];
    }

    private function register(string $email = 'jean@example.com', string $password = 'secret123'): array
    {
        return $this->postJson('/auth/register', ['email' => $email, 'password' => $password]);
    }

    // ---------- register ----------

    public function testRegisterCreatesAccount(): void
    {
        $data = $this->register();

        self::assertResponseStatusCodeSame(201);
        self::assertSame('jean@example.com', $data['email']);
        self::assertArrayHasKey('id', $data);
        self::assertTrue($data['isActive']);
        self::assertContains('ROLE_EMPLOYEE', $data['roles']);
    }

    public function testRegisterNeverExposesPassword(): void
    {
        $data = $this->register();

        self::assertArrayNotHasKey('password', $data);
        self::assertArrayNotHasKey('passwordHash', $data);
        self::assertStringNotContainsString('secret123', $this->client->getResponse()->getContent());
    }

    public function testRegisterStoresHashedPassword(): void
    {
        $this->register();

        $account = $this->em->getRepository(Account::class)->findOneBy(['email' => 'jean@example.com']);

        self::assertNotNull($account);
        self::assertNotSame('secret123', $account->getPasswordHash());
        self::assertNotEmpty($account->getPasswordHash());
    }

    public function testRegisterDuplicateEmailReturns409(): void
    {
        $this->register();
        $this->register();

        self::assertResponseStatusCodeSame(409);
    }

    public function testRegisterInvalidEmailReturns422(): void
    {
        $this->register('not-an-email');

        self::assertResponseStatusCodeSame(422);
    }

    public function testRegisterShortPasswordReturns422(): void
    {
        $this->register('jean@example.com', '123');

        self::assertResponseStatusCodeSame(422);
    }

    public function testRegisterMissingFieldsReturns422(): void
    {
        $this->postJson('/auth/register', []);

        self::assertResponseStatusCodeSame(422);
    }

    // ---------- login ----------

    public function testLoginReturnsJwtToken(): void
    {
        $this->register();

        $data = $this->postJson('/auth/login', ['email' => 'jean@example.com', 'password' => 'secret123']);

        self::assertResponseStatusCodeSame(200);
        self::assertArrayHasKey('token', $data);
        self::assertCount(3, explode('.', $data['token'])); // header.payload.signature
    }

    public function testLoginWrongPasswordReturns401(): void
    {
        $this->register();

        $this->postJson('/auth/login', ['email' => 'jean@example.com', 'password' => 'wrong']);

        self::assertResponseStatusCodeSame(401);
    }

    public function testLoginUnknownEmailReturns401(): void
    {
        $this->postJson('/auth/login', ['email' => 'ghost@example.com', 'password' => 'secret123']);

        self::assertResponseStatusCodeSame(401);
    }

    public function testLoginInactiveAccountIsRejected(): void
    {
        $this->register();
        $account = $this->em->getRepository(Account::class)->findOneBy(['email' => 'jean@example.com']);
        $account->setIsActive(false);
        $this->em->flush();

        $data = $this->postJson('/auth/login', ['email' => 'jean@example.com', 'password' => 'secret123']);

        self::assertResponseStatusCodeSame(401);
        self::assertArrayNotHasKey('token', $data);
    }
}