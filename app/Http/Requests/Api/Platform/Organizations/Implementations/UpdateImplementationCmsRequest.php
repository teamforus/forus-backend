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
            'informal_communication' => 'nullable|boolean',
            'media_uuid' => 'nullable|array',
            'media_uuid.*' => $this->mediaRule(),
            'pages' => ['array', new ImplementationPagesArrayKeysRule()],
            'pages.*' => 'array',
            'pages.*.content' => 'nullable|string|max:10000',
            'pages.*.external' => 'present|boolean',
            'pages.*.external_url' => 'nullable|string|max:300',
            'pages.*.media_uuid' => 'nullable|array',
            'pages.*.media_uuid.*' => $this->mediaRule(),
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
