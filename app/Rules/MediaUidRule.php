<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Gate;

/**
 * Class MediaUidRule
 * @package App\Rules
 */
class MediaUidRule implements Rule
{
    protected $type;
    protected $errorMessage;

    /**
     * Create a new rule instance.
     *
     * @param string $type
     */
    public function __construct(string $type)
    {
        $this->type = $type;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        if (!is_string($value)) {
            $this->errorMessage = trans('validation.string');
            return false;
        }

        if (!$media = media()->findByUid($value)) {
            $this->errorMessage = trans('validation.exists');
            return false;
        }

        if (!Gate::allows('destroy', $media)) {
            $this->errorMessage = trans('validation.in');
            return false;
        }

        if ($media->type !== $this->type) {
            $this->errorMessage = trans('validation.in');
            return false;
        }

        if ($media->mediable) {
            $this->errorMessage = trans('validation.in');
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
        return $this->errorMessage;
    }
}
