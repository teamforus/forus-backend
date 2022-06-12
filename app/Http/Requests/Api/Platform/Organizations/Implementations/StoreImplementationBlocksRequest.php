<?php

namespace App\Http\Requests\Api\Platform\Organizations\Implementations;

use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;

/**
 * Class StoreImplementationBlocksRequest
 * @property null|Organization $organization
 * @package App\Http\Requests\Api\Platform\Organizations\Funds
 */
class StoreImplementationBlocksRequest extends BaseFormRequest
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
            'blocks.*' => 'nullable|array',
            'blocks.*.label' => 'nullable|string|max:200',
            'blocks.*.title' => 'required|string|max:200',
            'blocks.*.description' => 'required|string|max:5000',
            'blocks.*.button_enabled' => 'nullable|boolean',
            'blocks.*.button_text' => 'nullable|required_if:blocks.*.button_enabled,1|string|max:200',
            'blocks.*.button_link' => 'nullable|required_if:blocks.*.button_enabled,1|string|max:200',
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
