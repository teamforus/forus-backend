<?php

namespace App\Services\IdentityProviderService\Http\Middleware;

use App\Services\IdentityProviderService\Models\IdentityProviderScimCredential;
use App\Services\IdentityProviderService\Responses\IdentityProviderScimResponse;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateIdentityProviderScim
{
    /**
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token || !Config::get('identity_providers.enabled')) {
            return IdentityProviderScimResponse::error(401, __('identity_provider.scim.invalid_credential'), headers: [
                'WWW-Authenticate' => 'Bearer',
            ]);
        }

        $credential = IdentityProviderScimCredential::where('token_hash', hash('sha256', $token))
            ->whereNull('revoked_at')
            ->whereRelation('connection', 'uid', $request->route('connection'))
            ->with('connection.organization')
            ->first();

        $connection = $credential?->connection;

        if (!$connection ||
            !$connection->isEntra() ||
            $connection->isDisconnected() ||
            !$connection->organization->allow_identity_providers ||
            !$connection->organization->allow_identity_provider_requester_provisioning) {
            return IdentityProviderScimResponse::error(401, __('identity_provider.scim.invalid_credential'), headers: [
                'WWW-Authenticate' => 'Bearer error="invalid_token"',
            ]);
        }

        $credential->update(['last_used_at' => now()]);
        $request->route()->setParameter('connection', $connection);

        return $next($request);
    }
}
