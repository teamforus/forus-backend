<?php

namespace App\Http\Requests\Api\Platform\Organizations\Implementations\ImplementationSocialMedia;

use App\Http\Requests\BaseFormRequest;
use App\Models\Implementation;
use App\Models\ImplementationSocialMedia;
use Illuminate\Validation\Rule;

class UpdateSocialMediaRequest extends BaseFormRequest
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
        /** @var ImplementationSocialMedia $social_media */
        $social_media = $this->route('implementation_social_media');
        /** @var Implementation $implementation */
        $implementation = $this->route('implementation');

        return [
            'type'  => [
                'required',
                'in:' . implode(',', ImplementationSocialMedia::TYPES),
                Rule::unique('implementation_social_media','type')->where(function ($query) use ($implementation) {
                    $query->where('implementation_id', $implementation->id);
                })->ignore($social_media->id),
            ],
            'link'  => 'required|string|min:5|max:100',
            'title' => 'nullable|string|max:100',
        ];
    }
}
