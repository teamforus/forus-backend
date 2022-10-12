<?php

namespace App\Http\Requests\Api\Platform\Organizations\Implementations;

use App\Models\ImplementationPage;
use App\Models\ImplementationPageConfig;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class UpdateImplementationConfigRequest extends FormRequest
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
            'config'             => 'required|array',
            'config.*.id'        => 'nullable|exists:implementation_page_configs,id',
            'config.*.page_key'  => [
                'nullable',
                Rule::in(Arr::pluck(ImplementationPage::PAGE_TYPES, 'key'))
            ],
            'config.*.page_config_key'  => [
                'nullable',
                Rule::in(Arr::pluck(ImplementationPageConfig::CONFIG_LIST, 'page_config_key'))
            ],
            'config.*.is_active' => 'nullable|boolean',
        ];
    }
}
