<?php

namespace App\Rules;

use App\Models\Fund;
use App\Models\FundCriterion;
use App\Models\PrevalidationRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class PrevalidationItemRule extends BaseRule
{
    /**
     * @var ?Fund null
     */
    private $fund;
    private $prefix;
    private $record_repo;
    private $record_types;
    private $csv_primary_key;

    /**
     * PrevalidationItemRule constructor.
     * @param Fund|null $fund
     * @param string $prefix
     */
    public function __construct(?Fund $fund = null, string $prefix = 'data.')
    {
        $this->fund = $fund;
        $this->prefix = $prefix;
        $this->record_repo = record_repo();
        $this->csv_primary_key = $fund->fund_config->csv_primary_key ?? null;
        $this->record_types = collect($this->record_repo->getRecordTypes())->keyBy('key');
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
            return $this->rejectWithMessage("Invalid record key!");
        }

        if (!$this->fund) {
            return $this->rejectWithMessage(trans('validation.required'));
        }

        if ($this->fund->fund_config->csv_primary_key === $key) {
            if ($this->doesPrimaryKeyExists($value)) {
                return $this->rejectWithMessage(trans('validation.exists'));
            }

            return true;
        }

        return $this->validateRecord($key, $value);
    }

    /**
     * @param $value
     * @return bool
     */
    private function doesPrimaryKeyExists($value): bool {
        return PrevalidationRecord::where(function (Builder $builder) use ($value) {
            $builder->where([
                'record_type_id' => $this->record_repo->getTypeIdByKey($this->csv_primary_key),
                'value' => $value
            ])->whereHas('prevalidation', function(Builder $builder)  {
                $builder->where([
                    'identity_address' => auth_address(),
                    'fund_id' => $this->fund->id
                ]);
            });
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
            return $this->rejectWithMessage("Invalid fund criteria rule!");
        }

        try {
            Validator::make([$key => $value], [
                $fundCriterion->record_type_key => ['required', $typeKey, $operatorRule]
            ])->validate();
        } catch (ValidationException $e) {
            return $this->rejectWithMessage(trans(
                'validation.' . rtrim($operator, ':') . '.' . $typeKey,
                $fundCriterion->only('value'))
            );
        }

        return true;
    }
}
