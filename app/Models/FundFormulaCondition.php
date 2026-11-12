<?php

namespace App\Models;

use DateTimeImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $fund_formula_id
 * @property int|null $fund_formula_product_id
 * @property int $record_type_id
 * @property string $operator
 * @property string $value
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read FundFormula|null $fund_formula
 * @property-read FundFormulaProduct|null $fund_formula_product
 * @property-read RecordType $record_type
 */
class FundFormulaCondition extends Model
{
    protected $fillable = [
        'fund_formula_id', 'fund_formula_product_id', 'record_type_id', 'operator', 'value',
    ];

    /**
     * @return BelongsTo
     */
    public function fund_formula(): BelongsTo
    {
        return $this->belongsTo(FundFormula::class);
    }

    /**
     * @return BelongsTo
     */
    public function fund_formula_product(): BelongsTo
    {
        return $this->belongsTo(FundFormulaProduct::class);
    }

    /**
     * @return BelongsTo
     */
    public function record_type(): BelongsTo
    {
        return $this->belongsTo(RecordType::class);
    }

    /**
     * @param mixed $value
     * @return bool
     */
    public function matchesValue(mixed $value): bool
    {
        if ($this->record_type->isTypeDate()) {
            $value = $this->parseConditionDate($value);
            $conditionValue = $this->parseConditionDate($this->value);
        } elseif (in_array($this->record_type->type, [RecordType::TYPE_NUMBER, RecordType::TYPE_SELECT_NUMBER])) {
            if (!is_numeric($value) || !is_numeric($this->value)) {
                return false;
            }

            $conditionValue = $this->value;
        } else {
            return false;
        }

        if ($value === null || $conditionValue === null) {
            return false;
        }

        return match ($this->operator) {
            '=' => $value == $conditionValue,
            '<' => $value < $conditionValue,
            '<=' => $value <= $conditionValue,
            '>' => $value > $conditionValue,
            '>=' => $value >= $conditionValue,
            default => false,
        };
    }

    /**
     * @param mixed $value
     * @return string|null
     */
    public function parseConditionDate(mixed $value): ?string
    {
        if (!is_string($value) || !preg_match('/^(\d{4}-\d{2}-\d{2}|\d{2}-\d{2}-\d{4})$/D', $value)) {
            return null;
        }

        foreach (['Y-m-d', 'd-m-Y'] as $format) {
            $date = DateTimeImmutable::createFromFormat('!' . $format, $value);

            if ($date && $date->format($format) === $value) {
                return $date->format('Y-m-d');
            }
        }

        return null;
    }
}
