<?php

namespace App\Http\Requests\Api\Platform\Organizations\Funds\Identities;

class SendIdentityNotificationRequest extends ExportIdentitiesRequest
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
        return array_merge(parent::rules(), [
            'subject' => 'required|string|min:5|max:400',
            'content' => 'required|string|min:5|max:16384',
        ]);
    }
}
