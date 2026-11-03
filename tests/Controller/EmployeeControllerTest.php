<?php

namespace App\Tests\Controller;

use App\Entity\Employee;
use App\Repository\EmployeeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class EmployeeControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;
    /** @var EntityRepository<Employee> $employeeRepository */
    private EntityRepository $employeeRepository;
    private string $path = '/employee/';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->employeeRepository = $this->manager->getRepository(Employee::class);

        foreach ($this->employeeRepository->findAll() as $object) {
            $this->manager->remove($object);
        }

        $this->manager->flush();
    }

    public function testIndex(): void
    {
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Employee index');

        // Use the $crawler to perform additional assertions e.g.
        // self::assertSame('Some text on the page', $crawler->filter('.p')->first()->text());
    }

    public function testNew(): void
    {
        $this->client->request('GET', sprintf('%snew', $this->path));

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm('Save', [
            'employee[firstName]' => 'Testing',
            'employee[lastName]' => 'Testing',
            'employee[dateAnciennete]' => 'Testing',
            'employee[weeklyHours]' => 'Testing',
            'employee[availableSlots]' => 'Testing',
            'employee[datesExclues]' => 'Testing',
            'employee[skills]' => 'Testing',
            'employee[isActive]' => 'Testing',
            'employee[createdAt]' => 'Testing',
            'employee[account]' => 'Testing',
        ]);

        self::assertResponseRedirects('/employee');

        self::assertSame(1, $this->employeeRepository->count([]));

        $this->markTestIncomplete('This test was generated');
    }

    public function testShow(): void
    {
        $fixture = new Employee();
        $fixture->setFirstName('My Title');
        $fixture->setLastName('My Title');
        $fixture->setDateAnciennete('My Title');
        $fixture->setWeeklyHours('My Title');
        $fixture->setAvailableSlots('My Title');
        $fixture->setDatesExclues('My Title');
        $fixture->setSkills('My Title');
        $fixture->setIsActive('My Title');
        $fixture->setCreatedAt('My Title');
        $fixture->setAccount('My Title');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Employee');

        // Use assertions to check that the properties are properly displayed.
        $this->markTestIncomplete('This test was generated');
    }

    public function testEdit(): void
    {
        $fixture = new Employee();
        $fixture->setFirstName('Value');
        $fixture->setLastName('Value');
        $fixture->setDateAnciennete('Value');
        $fixture->setWeeklyHours('Value');
        $fixture->setAvailableSlots('Value');
        $fixture->setDatesExclues('Value');
        $fixture->setSkills('Value');
        $fixture->setIsActive('Value');
        $fixture->setCreatedAt('Value');
        $fixture->setAccount('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));

        $this->client->submitForm('Update', [
            'employee[firstName]' => 'Something New',
            'employee[lastName]' => 'Something New',
            'employee[dateAnciennete]' => 'Something New',
            'employee[weeklyHours]' => 'Something New',
            'employee[availableSlots]' => 'Something New',
            'employee[datesExclues]' => 'Something New',
            'employee[skills]' => 'Something New',
            'employee[isActive]' => 'Something New',
            'employee[createdAt]' => 'Something New',
            'employee[account]' => 'Something New',
        ]);

        self::assertResponseRedirects('/employee');

        $fixture = $this->employeeRepository->findAll();

        self::assertSame('Something New', $fixture[0]->getFirstName());
        self::assertSame('Something New', $fixture[0]->getLastName());
        self::assertSame('Something New', $fixture[0]->getDateAnciennete());
        self::assertSame('Something New', $fixture[0]->getWeeklyHours());
        self::assertSame('Something New', $fixture[0]->getAvailableSlots());
        self::assertSame('Something New', $fixture[0]->getDatesExclues());
        self::assertSame('Something New', $fixture[0]->getSkills());
        self::assertSame('Something New', $fixture[0]->getIsActive());
        self::assertSame('Something New', $fixture[0]->getCreatedAt());
        self::assertSame('Something New', $fixture[0]->getAccount());

        $this->markTestIncomplete('This test was generated');
    }

    public function testRemove(): void
    {
        $fixture = new Employee();
        $fixture->setFirstName('Value');
        $fixture->setLastName('Value');
        $fixture->setDateAnciennete('Value');
        $fixture->setWeeklyHours('Value');
        $fixture->setAvailableSlots('Value');
        $fixture->setDatesExclues('Value');
        $fixture->setSkills('Value');
        $fixture->setIsActive('Value');
        $fixture->setCreatedAt('Value');
        $fixture->setAccount('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertResponseRedirects('/employee');
        self::assertSame(0, $this->employeeRepository->count([]));

        $this->markTestIncomplete('This test was generated');
    }
}
