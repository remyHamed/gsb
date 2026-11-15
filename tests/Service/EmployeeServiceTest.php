<?php

namespace App\Tests\Service;

use App\Entity\Employee;
use App\Exception\EmployeeNotFoundException;
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

    public function testFindByIdReturnsEmployee(): void
    {
        $employee = new Employee();

        $repo = $this->createMock(EmployeeRepositoryInterface::class);
        $repo->expects(self::once())
            ->method('find')
            ->with('018f0000-0000-7000-8000-000000000001')
            ->willReturn($employee);

        self::assertSame(
            $employee,
            (new EmployeeService($repo))->findById('018f0000-0000-7000-8000-000000000001'),
        );
    }

    public function testFindByIdThrowsWhenEmployeeDoesNotExist(): void
    {
        $repo = $this->createStub(EmployeeRepositoryInterface::class);
        $repo->method('find')->willReturn(null);

        $this->expectException(EmployeeNotFoundException::class);

        (new EmployeeService($repo))->findById('018f0000-0000-7000-8000-000000000001');
    }

    public function testFindByIdThrowsOnMalformedIdWithoutQueryingRepository(): void
    {
        $repo = $this->createMock(EmployeeRepositoryInterface::class);
        $repo->expects(self::never())->method('find');

        $this->expectException(EmployeeNotFoundException::class);

        (new EmployeeService($repo))->findById('not-a-uuid');
    }
}
