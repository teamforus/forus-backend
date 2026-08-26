<?php

namespace App\Rules;

use App\Services\FileService\FileUploadConfigService;
use Illuminate\Contracts\Validation\Rule;

class FileTypeRule implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        return is_string($value) && resolve(FileUploadConfigService::class)->isTypeAllowed($value);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return 'Invalid media type.';
    }
}
