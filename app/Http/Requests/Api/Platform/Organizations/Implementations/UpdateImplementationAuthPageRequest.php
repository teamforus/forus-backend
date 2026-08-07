<?php

namespace App\Http\Requests\Api\Platform\Organizations\Implementations;

use App\Http\Requests\BaseFormRequest;

class UpdateImplementationAuthPageRequest extends BaseFormRequest
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
            'auth_page_title' => 'required|string|max:100',
            'auth_page_login_title' => 'required|string|max:100',
            'auth_page_login_email' => 'required|boolean',
            'auth_page_login_digid' => 'required|boolean',
            'auth_page_login_openid' => 'required|boolean',
            'auth_page_login_qr' => 'required|boolean',
            'auth_page_info_enabled' => 'required|boolean',
            'auth_page_info_title' => 'nullable|string|max:100',
            'auth_page_info_description' => ['nullable', ...$this->markdownRules(0, 1000)],
            'auth_page_login_options' => [
                'array',
                function (string $attribute, array $value, callable $fail) {
                    if (empty($this->getRouteImplementation()->authPageUsableLoginOptions($value))) {
                        $fail('Selecteer minimaal een beschikbare inlogoptie.');
                    }
                },
            ],
        ];
    }

    /**
     * @return void
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'auth_page_login_options' => [
                'email' => $this->boolean('auth_page_login_email'),
                'digid' => $this->boolean('auth_page_login_digid'),
                'openid' => $this->boolean('auth_page_login_openid'),
                'qr' => $this->boolean('auth_page_login_qr'),
            ],
        ]);
    }

    /**
     * @return array
     */
    public function attributes(): array
    {
        return [
            'auth_page_title' => 'titel',
            'auth_page_login_title' => 'inlogsectie titel',
            'auth_page_login_options' => 'inlogopties',
            'auth_page_info_title' => 'uitlegsectie titel',
            'auth_page_info_description' => 'extra omschrijving',
        ];
    }
}
