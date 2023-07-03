<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\BaseFormRequest;
use App\Services\BIConnectionService\BIConnection;
use App\Services\BIConnectionService\Responses\UnauthorizedResponse;
use Illuminate\Support\Facades\Config;

class ExportBIConnectionRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return UnauthorizedResponse|bool
     */
    public function authorize(): UnauthorizedResponse|bool
    {
        if (!BIConnection::getBIConnectionFromRequest($this)) {
            return new UnauthorizedResponse();
        }

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
        $this->maxAttempts = Config::get('forus.bi_connections.throttle.throttle_attempts', 10);
        $this->decayMinutes = Config::get('forus.bi_connections.throttle.throttle_decay', 10);
        $this->throttleWithKey('to_many_attempts', $this, 'bi_connection');

        return [];
    }
}
