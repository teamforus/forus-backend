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
     * Get the validation rules that apply to the request.
     *
     * @return ((FileMimeTypeRule|\Illuminate\Validation\Rules\In|string)[]|mixed)[]
     *
     * @psalm-return array{file: list{'required', 'file', 'image',...}|mixed, type: list{'required', \Illuminate\Validation\Rules\In}|mixed,...}
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
     *
     * @return (\Illuminate\Validation\Rules\In|array|string)[][]
     *
     * @psalm-return array{sync_presets: list{'nullable', 'array'}, 'sync_presets.*': list{'nullable', \Illuminate\Validation\Rules\In|array<never, never>}}
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
