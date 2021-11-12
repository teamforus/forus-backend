<?php

namespace App\Http\Requests\Api\Platform\Organizations\Funds;

use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;

/**
 * Class UpdateFundRequest
 * @property null|Organization $organization
 * @package App\Http\Requests\Api\Platform\Organizations\Funds
 */
class StoreFundFaqRequest extends BaseFormRequest
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
            'faq'                       => 'present|array',
            'faq.*.title'               => 'required|string|max:100',
            'faq.*.description'         => 'required|string|max:5000',
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