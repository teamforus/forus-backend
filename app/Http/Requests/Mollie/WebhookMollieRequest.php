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
        return true;
    }

    /**
     * @return string[]
     */
    public function rules(): array
    {
        return [
            // 'id' => 'required|string',
        ];
    }
}
