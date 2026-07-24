<?php

namespace App\Http\Requests\Api\Platform\Organizations\Implementations\ImplementationPages;

use App\Http\Requests\BaseFormRequest;
use App\Models\ImplementationPage;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class IndexImplementationPageCmsBlockConfigsRequest extends BaseFormRequest
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
            'page_type' => [
                'nullable',
                Rule::in(Arr::pluck(ImplementationPage::PAGE_TYPES, 'key')),
            ],
        ];
    }
}
