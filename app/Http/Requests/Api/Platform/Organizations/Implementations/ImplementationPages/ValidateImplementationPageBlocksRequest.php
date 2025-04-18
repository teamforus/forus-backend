<?php

namespace App\Http\Requests\Api\Platform\Organizations\Implementations\ImplementationPages;

use App\Http\Requests\BaseFormRequest;
use App\Rules\MaxStringRule;
use App\Rules\MediaUidRule;

class ValidateImplementationPageBlocksRequest extends BaseFormRequest
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
            'blocks.*' => 'nullable|array',
            'blocks.*.label' => 'nullable|string|max:30',
            'blocks.*.title' => 'required|string|max:100',
            'blocks.*.description' => [
                'required',
                'string',
                new MaxStringRule(500),
            ],
            'blocks.*.button_enabled' => 'nullable|boolean',
            'blocks.*.button_text' => 'nullable|required_if:blocks.*.button_enabled,true|string|max:200',
            'blocks.*.button_link' => 'nullable|required_if:blocks.*.button_enabled,true|string|max:200',
            'blocks.*.button_link_label' => 'nullable|required_if:blocks.*.button_enabled,true|string|max:500',
            'blocks.*.button_target_blank' => 'nullable|required_if:blocks.*.button_enabled,true|boolean',
            'blocks.*.media_uid.*' => $this->blockMediaRule(),
        ];
    }

    /**
     * @return array
     */
    public function messages(): array
    {
        return [
            'blocks.*.title.required' => 'Het title veld is verplicht',
            'blocks.*.description.required' => 'Het description veld is verplicht',
            'blocks.*.button_text.required_if' => 'Het button text veld is verplicht',
            'blocks.*.button_link.required_if' => 'Het button link veld is verplicht',
            'blocks.*.button_link_label.required_if' => 'Het button link label veld is verplicht',
        ];
    }

    /**
     * @return array
     */
    private function blockMediaRule(): array
    {
        return [
            'nullable',
            'string',
            'exists:media,uid',
            new MediaUidRule('implementation_block_media'),
        ];
    }
}
