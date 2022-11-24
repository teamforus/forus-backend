<?php

namespace App\Http\Requests\Api\Media;

use App\Http\Requests\BaseFormRequest;
use App\Services\MediaService\Models\Media;
use Illuminate\Validation\Rule;

/**
 * @property-read Media $media_uid
 */
class CloneMediaRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $media = $this->media_uid;

        return [
            'type' => [
                'required',
                Rule::in([$media->type]),
            ],
        ];
    }
}
