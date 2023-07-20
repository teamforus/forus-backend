<?php

namespace App\Http\Requests\Api\Platform\OrganizationContacts;

use App\Http\Requests\BaseFormRequest;
use App\Models\OrganizationContact;
use Illuminate\Validation\Rule;

class StoreOrganizationContactsRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->isAuthenticated();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return array_merge([
            'contacts' => 'present|array',
            'contacts.*' => 'required|array',
            'contacts.*.type' => [
                'required',
                Rule::in(OrganizationContact::TYPES),
            ],
            'contacts.*.contact_key' => [
                'required',
                Rule::in(array_keys(OrganizationContact::$availableContacts))
            ],
        ], $this->getValueRules());
    }

    /**
     * @return array
     */
    public function getValueRules(): array
    {
        $rules = [];
        foreach($this->get('contacts') as $key => $contact) {
            $type = $this->get('contacts')[$key]['type'] ?? null;
            $rule = match($type) {
                OrganizationContact::TYPE_EMAIL => 'email',
                default => 'string',
            };

            $rules["contacts.$key.value"] = "nullable|$rule";
        }

        return $rules;
    }

    /**
     * @return string[]
     */
    public function attributes(): array
    {
        $attributes = [];
        foreach($this->get('contacts') as $key => $contact) {
            $attributes["contacts.$key.value"] = 'Contact';
        }

        return $attributes;
    }
}
