<?php

namespace App\Http\Requests\Api\Platform\Organizations\MollieConnections;

use App\Exceptions\AuthorizationJsonException;
use App\Models\Organization;
use Illuminate\Support\Facades\Config;

/**
 * @property-read Organization $organization
 */
class OauthMollieConnectionRequest extends BaseMollieConnectionRequest
{
    /**
     * @return void
     * @throws AuthorizationJsonException
     */
    protected function throttle(): void
    {
        $this->maxAttempts = Config::get('forus.throttles.mollie.connect.attempts');
        $this->decayMinutes = Config::get('forus.throttles.mollie.connect.decay');

        $this->throttleWithKey('to_many_attempts', $this, 'mollie_connection');
    }
}
