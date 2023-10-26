<?php

namespace App\Rules;

use App\Models\Fund;
use App\Models\FundCriterion;
use App\Models\PrevalidationRecord;
use App\Models\RecordType;
use App\Rules\FundRequests\BaseFundRequestRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

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
            !in_array($key, $this->fund->requiredPrevalidationKeys(true), true)) {
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

        if (!$criterion || !$validation) {
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
