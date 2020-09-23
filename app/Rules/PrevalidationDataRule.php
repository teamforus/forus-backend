<?php

namespace App\Rules;

use App\Models\Fund;
use Illuminate\Contracts\Validation\Rule;

/**
 * Class PrevalidationDataRule
 * @package App\Rules
 */
class PrevalidationDataRule implements Rule
{
    private $messageText;
    private $fundId;

    /**
     * Create a new rule instance.
     *
     * @param ?int $fundId
     * @return void
     */
    public function __construct(
        int $fundId = null
    ) {
        $this->fundId = $fundId;
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
        $data = collect($value);
        $recordRepo = resolve('forus.services.record');
        $recordTypes = collect($recordRepo->getRecordTypes())->pluck('key');

        if ($data->isEmpty()) {
            $this->messageText = trans('validation.prevalidated_empty_data');

            return false;
        }

        /** @var Fund $fund */
        $fund = $this->fundId ? Fund::query()->find($this->fundId) : false;
        $requiredKeys = $fund ? $fund->requiredPrevalidationKeys() : collect([]);

        foreach ($data as $records) {
            $records = collect($records);

            if ($fund && $records->keys()->search($fund->fund_config->csv_primary_key) === false) {
                $this->messageText = trans('validation.prevalidation_missing_primary_key');

                return false;
            }

            if ($fund && $records->keys()->intersect($requiredKeys)->count() < $requiredKeys->count()) {
                $this->messageText = trans('validation.prevalidation_missing_required_keys');
                return false;
            }

            foreach ($records as $recordKey => $record) {
                if ($recordTypes->search($recordKey) === false) {
                    $this->messageText = trans('validation.prevalidation_invalid_record_key');
                    return false;
                }

                if ($recordKey === 'primary_email') {
                    $this->messageText = trans('validation.prevalidation_invalid_type_primary_email');
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return $this->messageText;
    }
}
