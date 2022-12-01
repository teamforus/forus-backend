<?php

namespace App\Http\Requests\Api\Media;

use App\Http\Requests\BaseFormRequest;
use App\Services\MediaService\MediaConfig;
use App\Services\MediaService\MediaPreset;
use App\Services\MediaService\MediaService;
use App\Services\MediaService\Rules\FileMimeTypeRule;
use Illuminate\Validation\Rule;

class StoreMediaRequest extends BaseFormRequest
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
        $type = MediaService::getMediaConfig($this->input('type'));

        return array_merge([
            'file' => array_merge([
                'required',
                'file',
                'image',
            ], $type ? [
                'mimes:' . implode(',', $type->getSourceExtensions()),
                new FileMimeTypeRule($type->getSourceMimeTypes()),
                'max:' . $type->getMaxSourceFileSize(4096),
            ]: []),
            'type' => [
                'required',
                Rule::in(array_keys(MediaService::getMediaConfigs())),
            ],
        ], $this->syncPresetsRule($type));
    }

    /**
     * @param MediaConfig|null $type
     * @return array
     */
    protected function syncPresetsRule(?MediaConfig $type): array
    {
        return [
            'sync_presets' => [
                'nullable',
                'array',
            ],
            'sync_presets.*' => [
                'nullable',
                $type ? Rule::in(array_map(fn(MediaPreset $preset) => $preset->name, $type->getPresets())) : [],
            ],
        ];
    }
}
