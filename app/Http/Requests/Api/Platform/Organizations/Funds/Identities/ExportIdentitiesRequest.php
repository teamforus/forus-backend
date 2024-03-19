<?php

namespace App\Http\Requests\Api\Platform\Organizations\Funds\Identities;

class ExportIdentitiesRequest extends IndexIdentitiesRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return true
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return (mixed|string)[]
     *
     * @psalm-return array{per_page: 'nullable',...}
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'per_page' => 'nullable',
        ]);
    }
}
