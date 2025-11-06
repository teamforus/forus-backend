<?php

namespace App\Rules;

use App\Services\MediaService\Models\Media;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Gate;

class MediaUidRule implements Rule
{
    protected string $type;
    protected string $errorMessage;
    protected ?int $mediableId;

    /**
     * Create a new rule instance.
     *
     * @param string $type
     * @param int|null $mediableId
     */
    public function __construct(string $type, int $mediableId = null)
    {
        $this->type = $type;
        $this->mediableId = $mediableId;
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

        if (!$media = Media::findByUid($value)) {
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

        if ($media->mediable && $media->mediable->getKey() !== $this->mediableId) {
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
