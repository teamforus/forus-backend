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
            'blocks.*'              => 'nullable|array',
            'blocks.*.label'        => 'nullable|string|max:200',
            'blocks.*.title'        => 'nullable|required_if:blocks.*.type,"detailed"|string|max:200',
            'blocks.*.description'  => 'nullable|required_if:blocks.*.type,"detailed"|string|max:5000',
            'blocks.*.button_enabled'   => 'nullable|boolean',
            'blocks.*.button_text'      => 'nullable|required_if:blocks.*.button_enabled,true|string|max:200',
            'blocks.*.button_link'      => 'nullable|required_if:blocks.*.button_enabled,true|string|max:200',
        ];
    }

    /**
     * @return array
     */
    public function messages(): array
    {
        return [
            'blocks.*.title.required_if' => 'Het title veld is verplicht',
            'blocks.*.description.required_if' => 'Het description veld is verplicht',
            'blocks.*.button_text.required_if' => 'Het button text veld is verplicht',
            'blocks.*.button_link.required_if' => 'Het button link veld is verplicht',
        ];
    }
}
