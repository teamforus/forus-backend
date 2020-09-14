<?php

namespace App\Services\MediaService\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Http\Request;

class FileMimeTypeRule implements Rule
{
    /**
     * @var array list allowed mime types
     */
    protected $mimeTypes = [];

    /**
     * @var Request
     */
    protected $request = null;

    /**
     * Create a new rule instance.
     *
     * @param string|array $mimeTypes
     * @param Request|null $request
     */
    public function __construct(
        $mimeTypes,
        Request $request = null
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
    public function passes($attribute, $value)
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
    public function message()
    {
        return 'Invalid media type.';
    }
}
