<?php

namespace App\Http\Requests\Api\Platform\Organizations\Implementations;

use App\Models\Language;
use App\Rules\MaxStringRule;
use App\Rules\MediaUidRule;
use Illuminate\Foundation\Http\FormRequest;

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
        return array_merge([
            'title' => 'nullable|string|max:50',
            'description' => [
                'nullable',
                'string',
                new MaxStringRule(400),
            ],
            'description_alignment' => 'nullable|in:left,center,right',
            'informal_communication' => 'nullable|boolean',
            'banner_media_uid' => ['nullable', new MediaUidRule('implementation_banner')],
            'overlay_enabled' => 'nullable|boolean',
            'overlay_type' => 'nullable|in:color,dots,lines,points,circles',
            'overlay_opacity' => 'nullable|numeric|min:0|max:100',
            'banner_button' => 'sometimes|boolean',
            'banner_button_text' => 'sometimes|nullable|required_if_accepted:banner_button|string|min:0|max:100',
            'banner_button_url' => 'sometimes|nullable|required_if_accepted:banner_button|url|min:0|max:1500',
            'banner_button_target' => 'sometimes|required_if_accepted:banner_button|in:self,_blank',
            'banner_button_type' => 'sometimes|in:color,white',
            'banner_wide' => 'sometimes|boolean',
            'banner_collapse' => 'sometimes|boolean',
            'banner_position' => 'sometimes|in:left,center,right',
            'banner_color' => 'sometimes|hex_color',
            'banner_background' => 'sometimes|hex_color',
            'banner_background_mobile' => 'sometimes|boolean',
            'page_title_suffix' => 'nullable|string|max:60',
            'languages' => 'array',
            'languages.*' => 'required|in:' . Language::getAllLanguages()->pluck('id')->join(','),
        ], $this->announcementsRules(), $this->showBlockFlags());
    }

    /**
     * @return array
     */
    public function attributes(): array
    {
        return [
            'announcement.title' => 'titel',
            'announcement.description' => 'description',
            'page_title_suffix' => 'browser tab postfix',
        ];
    }

    /**
     * @return string[]
     */
    private function showBlockFlags(): array
    {
        return [
            'show_home_map' => 'nullable|bool',
            'show_office_map' => 'nullable|boolean',
            'show_home_products' => 'nullable|boolean',
            'show_providers_map' => 'nullable|boolean',
            'show_provider_map' => 'nullable|boolean',
            'show_voucher_map' => 'nullable|boolean',
            'show_product_map' => 'nullable|boolean',
            'show_terms_checkbox' => 'nullable|boolean',
            'show_privacy_checkbox' => 'nullable|boolean',
        ];
    }

    /**
     * @return string[]
     */
    private function announcementsRules(): array
    {
        return [
            'announcement' => 'nullable|array',
            'announcement.type' => 'nullable|in:warning,danger,success,primary,default',
            'announcement.title' => 'nullable|string|max:2000',
            'announcement.description' => 'nullable|string|max:8000',
            'announcement.expire_at' => 'nullable|date_format:Y-m-d',
            'announcement.active' => 'nullable|boolean',
            'announcement.replace' => 'nullable|boolean',
        ];
    }
}
