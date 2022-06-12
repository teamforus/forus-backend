<?php

namespace App\Http\Requests\Api\Platform\Organizations\Implementations;

use App\Http\Requests\BaseFormRequest;
use App\Rules\MediaUidRule;

class UpdateImplementationPageRequest extends BaseFormRequest
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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'content'               => 'nullable|string|max:10000',
            'content_alignment'     => 'nullable|in:left,center,right',
            'external'              => 'present|boolean',
            'external_url'          => 'nullable|string|max:300',
            'media_uid'             => 'nullable|array',
            'media_uid.*'           => $this->mediaRule(),

            'blocks.*'              => 'nullable|array',
            'blocks.*.label'        => 'nullable|string|max:200',
            'blocks.*.title'        => 'nullable|required_if:blocks.*.type,"detailed"|string|max:200',
            'blocks.*.description'  => 'nullable|required_if:blocks.*.type,"detailed"|string|max:5000',
            'blocks.*.button_enabled'   => 'nullable|required_if:blocks.*.type,"detailed"|boolean',
            'blocks.*.button_text'      => 'nullable|required_if:blocks.*.button_enabled,1|string|max:200',
            'blocks.*.button_link'      => 'nullable|required_if:blocks.*.button_enabled,1|string|max:200',
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
    public function messages(): array
    {
        return [
            'blocks.*.title.required' => 'Het title veld is verplicht',
            'blocks.*.description.required_if' => 'Het description veld is verplicht',
        ];
    }
}
