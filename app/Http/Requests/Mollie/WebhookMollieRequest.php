<?php

namespace App\Http\Requests\Mollie;

use App\Http\Requests\BaseFormRequest;

class WebhookMollieRequest extends BaseFormRequest
{


    /**
     * @return array
     *
     * @psalm-return array<never, never>
     */
    public function rules(): array
    {
        return [
            // 'id' => 'required|string',
        ];
    }
}
