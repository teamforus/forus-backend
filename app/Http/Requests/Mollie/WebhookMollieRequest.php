<?php

namespace App\Http\Requests\Mollie;

use App\Http\Requests\BaseFormRequest;

class WebhookMollieRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        if (!in_array($this->ip(), config('mollie.webhook_ip_whitelist'))) {
            abort(403, 'Invalid request.');
        }

        return true;
    }

    /**
     * @return array
     * @noinspection PhpUnused
     */
    public function rules(): array
    {
        return [
            'id' => 'required|string',
        ];
    }
}
