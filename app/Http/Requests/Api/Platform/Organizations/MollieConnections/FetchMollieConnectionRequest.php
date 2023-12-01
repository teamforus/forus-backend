<?php

namespace App\Http\Requests\Api\Platform\Organizations\MollieConnections;

use App\Exceptions\AuthorizationJsonException;
use App\Models\Organization;
use Illuminate\Support\Facades\Config;

/**
 * @property-read Organization $organization
 */
class FetchMollieConnectionRequest extends BaseMollieConnectionRequest
{
    /**
     * @return void
     * @throws AuthorizationJsonException
     */
    protected function throttle(): void
    {
        $this->maxAttempts = Config::get('forus.throttles.mollie.fetch_connections.attempts');
        $this->decayMinutes = Config::get('forus.throttles.mollie.fetch_connections.decay');

        $this->throttleWithKey('to_many_attempts', $this, 'mollie_connection');
    }
}
