<?php

namespace App\Rules;

use App\Models\Fund;
use Illuminate\Contracts\Validation\Rule;

class PrevalidationDataRule implements Rule
{
    private $message;
    private $fundId;

    /**
     * Create a new rule instance.
     *
     * @param $fundId int
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
    public function passes($attribute, $value)
    {
        $data = collect($value);
        $recordRepo = app()->make('forus.services.record');
        $recordTypes = collect($recordRepo->getRecordTypes())->pluck('key');

        if ($data->isEmpty()) {
            $this->message = trans(
                'validation.prevalidated_empty_data'
            );

            return false;
        }

        /** @var Fund $fund */
        $fund = $this->fundId ? Fund::query()->find($this->fundId) : false;
        $requiredKeys = $fund ? $fund->requiredPrevalidationKeys() : collect([]);

        foreach ($data as $records) {
            $records = collect($records);

            if ($fund && $records->keys()->search(
                $fund->fund_config->csv_primary_key
                ) === false) {
                $this->message = trans(
                    'validation.prevalidation_missing_primary_key'
                );

                return false;
            }

            if ($fund && $records->keys()->intersect(
                $requiredKeys
                )->count() < $requiredKeys->count()) {
                $this->message = trans(
                    'validation.prevalidation_missing_required_keys'
                );

                return false;
            }

            foreach ($records as $recordKey => $record) {
                if ($recordTypes->search($recordKey) === false) {
                    $this->message = trans(
                        'validation.prevalidation_invalid_record_key'
                    );

                    return false;
                }

                if ($recordKey == 'primary_email') {
                    $this->message = trans(
                        'validation.prevalidation_invalid_type_primary_email'
                    );

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
    public function message()
    {
        return $this->message;
    }
}
