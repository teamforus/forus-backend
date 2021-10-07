<?php

namespace App\Http\Requests\Api\Platform\Organizations\Funds;

use App\Http\Requests\BaseFormRequest;
use App\Models\Fund;
use App\Models\Organization;
use Illuminate\Validation\Rule;

/**
 * Class UpdateFundRequest
 * @property null|Fund $fund
 * @property null|Organization $organization
 * @package App\Http\Requests\Api\Platform\Organizations\Funds
 */
class UpdateFundCriteriaRequest extends BaseFormRequest
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
        $organization = $this->organization;
        $validators = $organization->organization_validators()->pluck('id');

        return [
            'criteria'                      => 'present|array',
            'criteria.*.id'                 => [
                'nullable', Rule::in($this->fund->criteria()->pluck('id'))
            ],
            'criteria.*.operator'           => 'required|in:=,<,>',
            'criteria.*.record_type_key'    => 'required|exists:record_types,key',
            'criteria.*.value'              => 'required|string|between:1,20',
            'criteria.*.show_attachment'    => 'nullable|boolean',
            'criteria.*.title'              => 'nullable|string|max:100',
            'criteria.*.description'        => 'nullable|string|max:4000',
            'criteria.*.validators'         => 'nullable|array',
            'criteria.*.validators.*'       => Rule::in($validators->toArray())
        ];
    }

    public function attributes(): array
    {
        $keys = array_dot(array_keys($this->rules()));

        return array_combine($keys, array_map(static function($key) {
            $value = last(explode('.', $key));
            return trans_fb("validation.attributes." . $value, $value);
        }, $keys));
    }
}
