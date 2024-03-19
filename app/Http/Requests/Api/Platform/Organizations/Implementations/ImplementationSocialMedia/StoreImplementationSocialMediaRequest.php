<?php

namespace App\Http\Requests\Api\Platform\Organizations\Implementations\ImplementationSocialMedia;

use App\Http\Requests\BaseFormRequest;
use App\Models\Implementation;
use App\Models\ImplementationSocialMedia;
use App\Models\Organization;
use Illuminate\Validation\Rule;
use Illuminate\Database\Query\Builder;

/**
 * @property Organization $organization
 * @property Implementation $implementation
 */
class StoreImplementationSocialMediaRequest extends BaseFormRequest
{


    /**
     * Get the validation rules that apply to the request.
     *
     * @return ((\Illuminate\Validation\Rules\Unique|string)[]|string)[]
     *
     * @psalm-return array{type: list{'required', string, \Illuminate\Validation\Rules\Unique}, url: 'required|string|url|min:5|max:200', title: 'nullable|string|max:200'}
     */
    public function rules(): array
    {
        return [
            'type'  => [
                'required',
                'in:' . implode(',', ImplementationSocialMedia::TYPES),
                Rule::unique('implementation_social_media','type')->where(fn (Builder $q) => $q->where([
                    'implementation_id' => $this->implementation->id,
                ])),
            ],
            'url' => 'required|string|url|min:5|max:200',
            'title' => 'nullable|string|max:200',
        ];
    }
}
