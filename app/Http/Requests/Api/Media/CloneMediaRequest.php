<?php

namespace App\Http\Requests\Api\Media;

use App\Services\MediaService\MediaService;
use App\Services\MediaService\Models\Media;

/**
 * @property-read Media $media_uid
 */
class CloneMediaRequest extends StoreMediaRequest
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
        return $this->syncPresetsRule(MediaService::getMediaConfig($this->media_uid->type));
    }
}
