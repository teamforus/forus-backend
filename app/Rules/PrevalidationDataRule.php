<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class PrevalidationDataRule implements Rule
{
    private $message;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {

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

        foreach ($data as $records) {
            $records = collect($records);

            if ($records->isEmpty()) {
                $this->message = trans(
                    'validation.prevalidated_empty_data_row'
                );

                return false;
            }

            foreach ($records as $recordKey => $record) {
                if ($recordTypes->search($recordKey) === false) {
                    $this->message = trans(
                        'validation.prevalidated_invalid_row_record_type'
                    );

                    return false;
                }

                if ($recordKey == 'primary_email') {
                    $this->message = trans(
                        'validation.prevalidated_primary_email_type'
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
