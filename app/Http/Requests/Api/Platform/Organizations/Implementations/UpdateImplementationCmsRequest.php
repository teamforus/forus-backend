<?php

namespace App\Http\Requests\Api\Platform\Organizations\Implementations;

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
            'description' => 'nullable|string|max:4000',
            'description_alignment' => 'nullable|in:left,center,right',
            'informal_communication' => 'nullable|boolean',
            'banner_media_uid' => ['nullable', new MediaUidRule('implementation_banner')],
            'overlay_enabled' => 'nullable|bool',
            'overlay_type' => 'nullable|in:color,dots,lines,points,circles',
            'overlay_opacity' => 'nullable|numeric|min:0|max:100',
            'header_text_color' => 'nullable|in:bright,dark,auto',
        ], $this->announcementsRules());
    }

    /**
     * @return array
     */
    private function mediaRule(): array {
        return [
            'required',
            'string',
            'exists:media,uid',
            new MediaUidRule('cms_media'),
        ];
    }

    /**
     * @return string[]
     */
    private function announcementsRules(): array
    {
        return [
            'announcement'              => 'nullable|array',
            'announcement.type'         => 'nullable|in:warning,danger,success,primary,default',
            'announcement.title'        => 'nullable|string|max:2000',
            'announcement.description'  => 'nullable|string|max:8000',
            'announcement.expire_at'    => 'nullable|date_format:Y-m-d',
            'announcement.active'       => 'nullable|boolean',
            'announcement.replace'      => 'nullable|boolean',
        ];
    }

    /**
     * @return array
     */
    public function attributes(): array
    {
        return [
            'announcement.title' => 'titel',
            'announcement.description' => 'description',
        ];
    }
}
