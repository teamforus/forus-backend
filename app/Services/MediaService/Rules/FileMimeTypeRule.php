<?php

namespace App\Services\MediaService\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Http\Request;

class FileMimeTypeRule implements Rule
{
    /**
     * @var array list allowed mime types
     */
    protected array $mimeTypes = [];

    /**
     * @var Request
     */
    protected mixed $request = null;

    /**
     * Create a new rule instance.
     *
     * @param array|string $mimeTypes
     * @param Request|null $request
     */
    public function __construct(
        array|string $mimeTypes,
        Request $request = null,
    ) {
        $this->mimeTypes = (array) $mimeTypes;
        $this->request = $request ?? request();
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
        if ($this->request->hasFile($attribute)) {
            return in_array(
                mime_content_type((string) request()->file($attribute)),
                $this->mimeTypes
            );
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
        return 'Invalid media type.';
    }
}
