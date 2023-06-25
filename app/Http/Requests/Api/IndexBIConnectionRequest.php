<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Support\Facades\Config;

class IndexBIConnectionRequest extends BaseFormRequest
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
     * @throws \App\Exceptions\AuthorizationJsonException
     */
    public function rules(): array
    {
        $this->maxAttempts = Config::get('forus.throttles.bi_connection.attempts', 10);
        $this->decayMinutes = Config::get('forus.throttles.bi_connection.decay', 10);
        $this->throttleWithKey('to_many_attempts', $this, 'bi_connection');

        return [];
    }
}
