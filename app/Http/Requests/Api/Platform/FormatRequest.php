<?php

namespace App\Http\Requests\Api\Platform;

use App\Http\Requests\BaseFormRequest;

class FormatRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->isAuthenticated() && $this->identity()?->employees()->exists();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'markdown' => 'nullable|string|max:10000',
        ];
    }
}
