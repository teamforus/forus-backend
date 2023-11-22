<?php

namespace App\Http\Requests\Api\Platform\Organizations\Implementations;

use App\Http\Requests\BaseFormRequest;
use App\Rules\MediaUidRule;

class UpdatePreCheckBannerRequest extends BaseFormRequest
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
            'pre_check_media_uid' => ['nullable', new MediaUidRule('pre_check_banner')],
            'pre_check_homepage_title' => 'required|string|max:50',
            'pre_check_homepage_description' => 'required|string|max:1000',
            'pre_check_homepage_label' => 'nullable|string|max:50',
        ];
    }
}
