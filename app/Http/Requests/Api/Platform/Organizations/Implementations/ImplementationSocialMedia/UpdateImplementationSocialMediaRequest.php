<?php

namespace App\Http\Requests\Api\Platform\Organizations\Implementations\ImplementationSocialMedia;

use App\Http\Requests\BaseFormRequest;
use App\Models\Implementation;
use App\Models\ImplementationSocialMedia;
use App\Models\Organization;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;

/**
 * @property Organization $organization
 * @property Implementation $implementation
 * @property ImplementationSocialMedia $social_media
 */
class UpdateImplementationSocialMediaRequest extends BaseFormRequest
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
            'type'  => [
                'required',
                'in:' . implode(',', ImplementationSocialMedia::TYPES),
                Rule::unique('implementation_social_media','type')->where(fn (Builder $q) => $q->where([
                    'implementation_id' => $this->implementation->id,
                ]))->ignore($this->social_media->id),
            ],
            'url' => 'required|string|url|min:5|max:200',
            'title' => 'nullable|string|max:200',
        ];
    }
}
