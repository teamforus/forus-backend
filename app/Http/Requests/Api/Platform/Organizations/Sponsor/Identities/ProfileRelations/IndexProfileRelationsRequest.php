<?php

namespace App\Http\Requests\Api\Platform\Organizations\Sponsor\Identities\ProfileRelations;

use App\Http\Requests\BaseFormRequest;

class IndexProfileRelationsRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true; // handled via policy in controller
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'per_page' => $this->perPageRule(),
        ];
    }
}
