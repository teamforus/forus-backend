<?php

namespace App\Services\IdentityProviderService\Http\Requests;

use App\Services\IdentityProviderService\Exceptions\IdentityProviderScimException;
use App\Services\IdentityProviderService\Rules\ScimFilterRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class IndexIdentityProviderScimUsersRequest extends FormRequest
{
    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'filter' => ['bail', 'nullable', 'string', 'max:2048', new ScimFilterRule()],
            'startIndex' => 'nullable|integer',
            'count' => 'nullable|integer',
            'sortBy' => 'prohibited',
            'sortOrder' => 'prohibited',
            'attributes' => 'prohibited',
            'excludedAttributes' => 'prohibited',
        ];
    }

    /**
     * @return array{attribute: string, value: string}|null
     */
    public function scimFilter(): ?array
    {
        return ScimFilterRule::parse($this->input('filter'));
    }

    /**
     * @param Validator $validator
     * @throws IdentityProviderScimException
     * @throws ValidationException
     * @return void
     */
    protected function failedValidation(Validator $validator): void
    {
        if ($validator->errors()->has('filter')) {
            throw new IdentityProviderScimException(__('identity_provider.scim.invalid_filter'), 400, 'invalidFilter');
        }

        parent::failedValidation($validator);
    }
}
