<?php

namespace App\Models\Traits;

use App\Models\FundFormulaCondition;
use Illuminate\Database\Eloquent\Collection;

/**
 * @property-read Collection<int, FundFormulaCondition> $conditions
 */
trait HasFormulaConditions
{
    /**
     * @param array<string, mixed> $values
     * @return bool
     */
    public function matchesConditions(array $values): bool
    {
        foreach ($this->conditions as $condition) {
            if (!$condition->matchesValue($values[$condition->record_type->key] ?? null)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<string>
     */
    public function conditionRecordKeys(): array
    {
        return $this->conditions->pluck('record_type.key')->all();
    }
}
