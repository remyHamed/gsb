<?php

namespace App\Tests\Controller;

use App\Entity\Week;
use App\Repository\WeekRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class WeekControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;
    /** @var EntityRepository<Week> $weekRepository */
    private EntityRepository $weekRepository;
    private string $path = '/week/';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->weekRepository = $this->manager->getRepository(Week::class);

        foreach ($this->weekRepository->findAll() as $object) {
            $this->manager->remove($object);
        }

        $this->manager->flush();
    }

    public function testIndex(): void
    {
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Week index');

        // Use the $crawler to perform additional assertions e.g.
        // self::assertSame('Some text on the page', $crawler->filter('.p')->first()->text());
    }

    public function testNew(): void
    {
        $this->client->request('GET', sprintf('%snew', $this->path));

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm('Save', [
            'week[year]' => 'Testing',
            'week[weekNumber]' => 'Testing',
            'week[startDate]' => 'Testing',
        ]);

        self::assertResponseRedirects('/week');

        self::assertSame(1, $this->weekRepository->count([]));

        $this->markTestIncomplete('This test was generated');
    }

    public function testShow(): void
    {
        $fixture = new Week();
        $fixture->setYear('My Title');
        $fixture->setWeekNumber('My Title');
        $fixture->setStartDate('My Title');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Week');

        // Use assertions to check that the properties are properly displayed.
        $this->markTestIncomplete('This test was generated');
    }

    public function testEdit(): void
    {
        $fixture = new Week();
        $fixture->setYear('Value');
        $fixture->setWeekNumber('Value');
        $fixture->setStartDate('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));

        $this->client->submitForm('Update', [
            'week[year]' => 'Something New',
            'week[weekNumber]' => 'Something New',
            'week[startDate]' => 'Something New',
        ]);

        self::assertResponseRedirects('/week');

        $fixture = $this->weekRepository->findAll();

        self::assertSame('Something New', $fixture[0]->getYear());
        self::assertSame('Something New', $fixture[0]->getWeekNumber());
        self::assertSame('Something New', $fixture[0]->getStartDate());

        $this->markTestIncomplete('This test was generated');
    }

    public function testRemove(): void
    {
        $fixture = new Week();
        $fixture->setYear('Value');
        $fixture->setWeekNumber('Value');
        $fixture->setStartDate('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertResponseRedirects('/week');
        self::assertSame(0, $this->weekRepository->count([]));

        $this->markTestIncomplete('This test was generated');
    }
}
