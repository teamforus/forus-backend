<?php

namespace App\Rules;

use App\Models\Fund;
use App\Models\FundCriterion;
use App\Models\PrevalidationRecord;
use App\Models\RecordType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class PrevalidationItemRule extends BaseRule
{
    private ?Fund $fund;
    private string $prefix;
    private Collection $record_types;
    private ?string $csv_primary_key;

    /**
     * PrevalidationItemRule constructor.
     * @param Fund|null $fund
     * @param string $prefix
     */
    public function __construct(?Fund $fund = null, string $prefix = 'data.')
    {
        $this->fund = $fund;
        $this->prefix = $prefix;
        $this->csv_primary_key = $fund->fund_config->csv_primary_key ?? null;
        $this->record_types = RecordType::search()->keyBy('key');
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        $key = ltrim($attribute, $this->prefix);

        if (!$this->record_types->has($key) ||
            $this->fund->requiredPrevalidationKeys()->search($key, true) === false) {
            return $this->reject("Invalid record key!");
        }

        if (!$this->fund) {
            return $this->reject(trans('validation.required'));
        }

        if ($this->fund->fund_config->csv_primary_key === $key) {
            if ($this->doesPrimaryKeyExists($value)) {
                return $this->reject(trans('validation.exists'));
            }

            return true;
        }

        return $this->validateRecord($key, $value);
    }

    /**
     * @param $value
     * @return bool
     */
    private function doesPrimaryKeyExists($value): bool
    {
        return PrevalidationRecord::where(function (Builder $builder) use ($value) {
            $builder->where('value', '=', $value);
            $builder->whereRelation('record_type', 'record_types.key', '=', $this->csv_primary_key);
            $builder->whereRelation('prevalidation', [
                'identity_address' => auth()->id(),
                'fund_id' => $this->fund->id
            ]);
        })->exists();
    }

    /**
     * @param string $key
     * @param $value
     * @return bool
     */
    private function validateRecord(string $key, $value): bool
    {
        /** @var FundCriterion $fundCriterion */
        $fundCriterion = $this->fund->criteria->where('record_type_key', $key)->first();
        $recordType = $this->record_types[$fundCriterion->record_type_key];

        $typeKey = [
            'number' => 'numeric',
            'string' => 'string',
        ][$recordType['type']];

        $operator = [
            '<'     => 'lt:',
            '<='    => 'lte:',
            '='     => 'in:',
            '>'     => 'gt:',
            '>='    => 'gte:',
        ][$fundCriterion->operator];

        if ($typeKey === 'numeric') {
            $operatorRule = $operator . $fundCriterion->value;
        } elseif ($typeKey === 'string') {
            $operatorRule = \Illuminate\Validation\Rule::in($fundCriterion->value);
        } else {
            return $this->reject("Invalid fund criteria rule!");
        }

        try {
            Validator::make([$key => $value], [
                $fundCriterion->record_type_key => ['required', $typeKey, $operatorRule]
            ])->validate();
        } catch (ValidationException) {
            return $this->reject(trans(
                'validation.' . rtrim($operator, ':') . '.' . $typeKey,
                $fundCriterion->only('value'))
            );
        }

        return true;
    }
}
