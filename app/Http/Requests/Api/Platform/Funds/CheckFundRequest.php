<?php

namespace App\Http\Requests\Api\Platform\Funds;

use App\Exceptions\AuthorizationJsonException;
use App\Http\Requests\BaseFormRequest;
use App\Traits\ThrottleWithMeta;
use Illuminate\Support\Facades\Config;

class CheckFundRequest extends BaseFormRequest
{
    use ThrottleWithMeta;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     * @throws AuthorizationJsonException
     */
    public function authorize(): bool
    {
        $this->maxAttempts = Config::get('forus.throttles.fund_check.attempts');
        $this->decayMinutes = Config::get('forus.throttles.fund_check.decay');

        $this->throttleWithKey('to_many_attempts', $this, 'fund_check');

        return true;
    }
}
