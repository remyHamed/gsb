<?php

namespace App\Service\Impl\Scheduling;

use App\Entity\Employee;
use App\Entity\Shift;
use App\Service\Contract\Scheduling\EligibilityEvaluatorInterface;
use App\Service\Contract\Scheduling\EligibilityRuleInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final class EligibilityEvaluator implements EligibilityEvaluatorInterface
{
    /**
     * @param iterable<EligibilityRuleInterface> $rules
     */
    public function __construct(
        #[AutowireIterator('app.scheduling.eligibility_rule')]
        private readonly iterable $rules,
    ) {
    }

    public function filterEligible(iterable $employees, Shift $shift, AssignedHoursTracker $tracker): array
    {
        $eligible = [];

        foreach ($employees as $employee) {
            if ($this->isEligible($employee, $shift, $tracker)) {
                $eligible[] = $employee;
            }
        }

        return $eligible;
    }

    private function isEligible(Employee $employee, Shift $shift, AssignedHoursTracker $tracker): bool
    {
        foreach ($this->rules as $rule) {
            if (!$rule->isSatisfiedBy($employee, $shift, $tracker)) {
                return false;
            }
        }

        return true;
    }
}