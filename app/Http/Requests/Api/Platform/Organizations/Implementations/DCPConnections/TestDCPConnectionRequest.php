<?php

namespace App\Http\Requests\Api\Platform\Organizations\Implementations\DCPConnections;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Support\Facades\Config;
use Illuminate\Validation\Rule;

class TestDCPConnectionRequest extends BaseFormRequest
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
     * @return array
     */
    public function rules(): array
    {
        return [
            'url' => ['nullable', 'string', Rule::in(Config::get('dcp.allowed_urls', []))],
            'username' => 'nullable|string|max:200',
            'password' => 'nullable|string|max:200',
        ];
    }
}
