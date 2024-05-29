<?php

namespace App\Rules;

use App\Models\Fund;
use App\Models\FundCriterion;
use App\Models\PrevalidationRecord;
use App\Models\RecordType;
use App\Rules\FundRequests\BaseFundRequestRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class PrevalidationItemRule extends BaseRule
{
    private Collection $record_types;
    private ?string $csv_primary_key;

    /**
     * PrevalidationItemRule constructor.
     * @param Fund|null $fund
     * @param array $recordValues
     * @param string $prefix
     */
    public function __construct(
        public ?Fund $fund,
        public array $recordValues,
        public string $prefix
    ) {
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
        if (!Str::startsWith($attribute, $this->prefix)) {
            return $this->reject("Invalid record key!");
        }

        $key = substr($attribute, strlen($this->prefix));

        if (!$this->record_types->has($key) ||
            !in_array($key, $this->fund->requiredPrevalidationKeys(true, $this->recordValues), true)) {
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
        /** @var FundCriterion $criterion */
        $criterion = $this->fund->criteria->where('record_type_key', $key)->first();

        $validation = $criterion ? BaseFundRequestRule::validateRecordValue($criterion, $value) : null;
        $excluded = $criterion && $criterion->isExcludedByRules($this->recordValues);

        if (!$excluded && (!$criterion || !$validation)) {
            return $this->reject(trans('validation.in', [
                'attribute' => trans('validation.attributes.value'),
            ]));
        }

        if (!$validation->passes()) {
            return $this->reject($validation->errors()->first('value'));
        }

        return true;
    }
}
