<?php

namespace App\Http\Requests\Api\Platform\Organizations\Implementations;

use App\Rules\ImplementationPagesArrayKeysRule;
use App\Rules\MediaUidRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class UpdateImplementationCmsRequest
 * @package App\Http\Requests\Api\Platform\Organizations\Implementations
 */
class UpdateImplementationCmsRequest extends FormRequest
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
        return [
            'title' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:4000',
            'description_alignment' => 'nullable|in:left,center,right',
            'informal_communication' => 'nullable|boolean',
            'banner_media_uid' => ['nullable', new MediaUidRule('implementation_banner')],
            'media_uid' => 'nullable|array',
            'media_uid.*' => $this->mediaRule(),
            'pages' => ['array', new ImplementationPagesArrayKeysRule()],

            'overlay_enabled' => 'nullable|bool',
            'overlay_type' => 'nullable|in:color,dots,lines,points,circles',
            'overlay_opacity' => 'nullable|numeric|min:0|max:100',
            'header_text_color' => 'nullable|in:bright,dark,auto',
        ];
    }

    /**
     * @return array
     */
    private function mediaRule(): array {
        return [
            'required',
            'string',
            'exists:media,uid',
            new MediaUidRule('cms_media')
        ];
    }

    /**
     * @return array
     */
    public function attributes(): array
    {
        return [
            'pages.privacy.external_url' => 'External url',
            'pages.privacy.external' => 'External',
            'pages.privacy.content' => 'Content',
        ];
    }
}
