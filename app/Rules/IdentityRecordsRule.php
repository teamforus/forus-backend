<?php

namespace App\Rules;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Contracts\Validation\Rule;

/**
 * Class IdentityRecordsRule
 * @package App\Rules
 */
class IdentityRecordsRule implements Rule
{
    private $recordRepo;
    private $message;

    /**
     * Create a new rule instance.
     *
     * @param BaseFormRequest $request
     */
    public function __construct(BaseFormRequest $request)
    {
        $this->recordRepo = $request->records_repo();
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
        if (!is_array($value)) {
            $this->message = trans('validation.array');
            return false;
        }

        $invalidKeys = array_diff(array_merge(array_keys($value)), array_merge(
            array_pluck($this->recordRepo->getRecordTypes(false), 'key'),
            env('DISABLE_DEPRECATED_API') ? [] : ['primary_email']
        ));

        if (count($invalidKeys) > 0) {
            $this->message = trans('validation.unknown_record_key', [
                'key' => array_first($invalidKeys)
            ]);

            return false;
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
        return $this->message;
    }
}
