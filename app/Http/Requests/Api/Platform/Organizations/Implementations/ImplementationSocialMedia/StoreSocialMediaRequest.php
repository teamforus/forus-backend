<?php

namespace App\Http\Requests\Api\Platform\Organizations\Implementations\ImplementationSocialMedia;

use App\Http\Requests\BaseFormRequest;
use App\Models\Implementation;
use App\Models\ImplementationSocialMedia;
use App\Models\Organization;
use Illuminate\Validation\Rule;

/**
 * Class StoreSocialMediaRequest
 * @property Organization|null $organization
 * @package App\Http\Requests\Api\Platform\Organizations
 */
class StoreSocialMediaRequest extends BaseFormRequest
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
        /** @var Implementation $implementation */
        $implementation = $this->route('implementation');

        return [
            'type'  => [
                'required',
                'in:' . implode(',', ImplementationSocialMedia::TYPES),
                Rule::unique('implementation_social_media','type')->where(function ($query) use ($implementation) {
                    $query->where('implementation_id', $implementation->id);
                }),
            ],
            'link'  => 'required|string|min:5|max:100',
            'title' => 'nullable|string|max:100',
        ];
    }
}
