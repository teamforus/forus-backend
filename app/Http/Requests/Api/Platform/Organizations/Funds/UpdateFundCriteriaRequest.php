<?php

namespace App\Http\Requests\Api\Platform\Organizations\Funds;

use App\Models\Fund;
use App\Models\Organization;
use App\Services\Forus\Record\Models\RecordType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Class UpdateFundRequest
 * @property null|Organization $organization
 * @package App\Http\Requests\Api\Platform\Organizations\Funds
 */
class UpdateFundCriteriaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $organization = $this->organization;
        $validators = $organization->organization_validators()->pluck('id');

        return [
            'criteria'                      => 'present|array',
            'criteria.*.operator'           => 'required|in:=,<,>',
            'criteria.*.record_type_key'    => [
                'required',
                Rule::in(RecordType::query()->pluck('key')->toArray())
            ],
            'criteria.*.value'              => 'required|string|between:1,10',
            'criteria.*.show_attachment'    => 'nullable|boolean',
            'criteria.*.description'        => 'nullable|string|max:4000',
            'criteria.*.validators'         => 'nullable|array',
            'criteria.*.validators.*'       => Rule::in($validators->toArray())
        ];
    }

    public function attributes()
    {
        $keys = array_dot(array_keys($this->rules()));

        return array_combine($keys, array_map(function($key) {
            $value = last(explode('.', $key));
            return trans_fb("validation.attributes." . $value, $value);
        }, $keys));
    }
}
