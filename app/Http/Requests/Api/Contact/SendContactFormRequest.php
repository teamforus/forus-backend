<?php

namespace App\Http\Requests\Api\Contact;

use App\Exceptions\AuthorizationJsonException;
use App\Http\Requests\BaseFormRequest;
use App\Traits\ThrottleWithMeta;
use Illuminate\Support\Facades\Config;

class SendContactFormRequest extends BaseFormRequest
{
    use ThrottleWithMeta;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return ((mixed|string)[]|string)[]
     *
     * @psalm-return array{name: 'required|string', email: array{0: 'required'|mixed,...}, phone: 'nullable|string', organization: 'nullable|string', message: 'required|string'}
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'email' => [
                'required',
                ...$this->emailRules(),
            ],
            'phone' => 'nullable|string',
            'organization' => 'nullable|string',
            'message' => 'required|string',
        ];
    }
}