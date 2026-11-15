<?php

namespace App\Tests\Service;

use App\Entity\Employee;
use App\Repository\Contract\EmployeeRepositoryInterface;
use App\Service\Impl\EmployeeService;
use PHPUnit\Framework\TestCase;

final class EmployeeServiceTest extends TestCase
{
    public function testFindAllReturnsRepositoryResult(): void
    {
        $expected = [new Employee(), new Employee()];

        $repo = $this->createMock(EmployeeRepositoryInterface::class);
        $repo->expects(self::once())->method('findAll')->willReturn($expected);

        self::assertSame($expected, (new EmployeeService($repo))->findAll());
    }

    public function testFindActiveReturnsRepositoryResult(): void
    {
        $expected = [new Employee()];

        $repo = $this->createMock(EmployeeRepositoryInterface::class);
        $repo->expects(self::once())->method('findActive')->willReturn($expected);

        self::assertSame($expected, (new EmployeeService($repo))->findActive());
    }

    public function testFindActiveReturnsEmptyArrayWhenNobodyIsActive(): void
    {
        $repo = $this->createStub(EmployeeRepositoryInterface::class);
        $repo->method('findActive')->willReturn([]);

        self::assertSame([], (new EmployeeService($repo))->findActive());
    }
}