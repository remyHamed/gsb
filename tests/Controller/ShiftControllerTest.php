<?php

namespace App\Tests\Controller;

use App\Entity\Shift;
use App\Repository\ShiftRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ShiftControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;
    /** @var EntityRepository<Shift> $shiftRepository */
    private EntityRepository $shiftRepository;
    private string $path = '/shift/';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->shiftRepository = $this->manager->getRepository(Shift::class);

        foreach ($this->shiftRepository->findAll() as $object) {
            $this->manager->remove($object);
        }

        $this->manager->flush();
    }

    public function testIndex(): void
    {
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Shift index');

        // Use the $crawler to perform additional assertions e.g.
        // self::assertSame('Some text on the page', $crawler->filter('.p')->first()->text());
    }

    public function testNew(): void
    {
        $this->client->request('GET', sprintf('%snew', $this->path));

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm('Save', [
            'shift[date]' => 'Testing',
            'shift[startTime]' => 'Testing',
            'shift[endTime]' => 'Testing',
            'shift[skillsRequired]' => 'Testing',
            'shift[week]' => 'Testing',
            'shift[assignedEmployees]' => 'Testing',
        ]);

        self::assertResponseRedirects('/shift');

        self::assertSame(1, $this->shiftRepository->count([]));

        $this->markTestIncomplete('This test was generated');
    }

    public function testShow(): void
    {
        $fixture = new Shift();
        $fixture->setDate('My Title');
        $fixture->setStartTime('My Title');
        $fixture->setEndTime('My Title');
        $fixture->setSkillsRequired('My Title');
        $fixture->setWeek('My Title');
        $fixture->setAssignedEmployees('My Title');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Shift');

        // Use assertions to check that the properties are properly displayed.
        $this->markTestIncomplete('This test was generated');
    }

    public function testEdit(): void
    {
        $fixture = new Shift();
        $fixture->setDate('Value');
        $fixture->setStartTime('Value');
        $fixture->setEndTime('Value');
        $fixture->setSkillsRequired('Value');
        $fixture->setWeek('Value');
        $fixture->setAssignedEmployees('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));

        $this->client->submitForm('Update', [
            'shift[date]' => 'Something New',
            'shift[startTime]' => 'Something New',
            'shift[endTime]' => 'Something New',
            'shift[skillsRequired]' => 'Something New',
            'shift[week]' => 'Something New',
            'shift[assignedEmployees]' => 'Something New',
        ]);

        self::assertResponseRedirects('/shift');

        $fixture = $this->shiftRepository->findAll();

        self::assertSame('Something New', $fixture[0]->getDate());
        self::assertSame('Something New', $fixture[0]->getStartTime());
        self::assertSame('Something New', $fixture[0]->getEndTime());
        self::assertSame('Something New', $fixture[0]->getSkillsRequired());
        self::assertSame('Something New', $fixture[0]->getWeek());
        self::assertSame('Something New', $fixture[0]->getAssignedEmployees());

        $this->markTestIncomplete('This test was generated');
    }

    public function testRemove(): void
    {
        $fixture = new Shift();
        $fixture->setDate('Value');
        $fixture->setStartTime('Value');
        $fixture->setEndTime('Value');
        $fixture->setSkillsRequired('Value');
        $fixture->setWeek('Value');
        $fixture->setAssignedEmployees('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertResponseRedirects('/shift');
        self::assertSame(0, $this->shiftRepository->count([]));

        $this->markTestIncomplete('This test was generated');
    }
}
