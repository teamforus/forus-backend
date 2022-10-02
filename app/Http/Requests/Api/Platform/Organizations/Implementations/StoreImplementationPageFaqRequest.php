<?php

namespace App\Http\Requests\Api\Platform\Organizations\Implementations;

use App\Http\Requests\BaseFormRequest;

class StoreImplementationPageFaqRequest extends BaseFormRequest
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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'faq'                       => 'present|array',
            'faq.*'                     => 'required|array',
            'faq.*.title'               => 'required|string|max:200',
            'faq.*.description'         => 'required|string|max:5000',
        ];
    }

    /**
     * @return array
     */
    public function attributes(): array
    {
        $keys = array_dot(array_keys($this->rules()));

        return array_combine($keys, array_map(static function($key) {
            $value = last(explode('.', $key));
            return trans_fb("validation.attributes." . $value, $value);
        }, $keys));
    }
}