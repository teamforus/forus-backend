<?php

namespace App\Rules;

use App\Services\FileService\Models\File;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

class FileUidRule implements Rule
{
    protected string $type;
    protected string $errorMessage;
    protected Model|null $targetFileable;

    /**
     * Create a new rule instance.
     *
     * @param string $type
     * @param Model|null $targetFileable
     */
    public function __construct(string $type, ?Model $targetFileable = null)
    {
        $this->type = $type;
        $this->targetFileable = $targetFileable;
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

        if (!$file = File::findByUid($value)) {
            $this->errorMessage = trans('validation.exists');
            return false;
        }

        if (!Gate::allows('destroy', $file)) {
            $this->errorMessage = trans('validation.in');
            return false;
        }

        if ($file->type !== $this->type) {
            $this->errorMessage = trans('validation.in');
            return false;
        }

        if ($file->fileable && !$this->isSameFileable($file->fileable, $this->targetFileable)) {
            $this->errorMessage = trans('validation.in');
            return false;
        }

        return true;
    }

    /**
     * @param Model $fileable
     * @param Model|null $targetFileable
     * @return bool
     */
    public function isSameFileable(Model $fileable, ?Model $targetFileable): bool
    {
        return
            $targetFileable &&
            $fileable instanceof $targetFileable &&
            $fileable->getKey() === $targetFileable->getKey();
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
