<?php

namespace App\Http\Resources;

use App\Http\Requests\BaseFormRequest;
use App\Models\Identity;
use App\Services\IdentityProviderService\Models\IdentityProviderConnection;
use App\Services\IdentityProviderService\Models\IdentityProviderMembership;
use App\Services\IdentityProviderService\Queries\IdentityProviderConnectionQuery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * @property-read Identity $resource
 */
class IdentityResource extends BaseJsonResource
{
    public const array LOAD = [
        'record_bsn', 'identity_provider_memberships_claimed',
    ];

    public static $wrap = null;

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $request = BaseFormRequest::createFrom($request);
        $identity = $this->resource;

        return [
            'address' => $identity->address,
            ...$this->privateFields($request, $identity),
        ];
    }

    /**
     * @param BaseFormRequest $request
     * @param Identity $identity
     * @return array
     */
    protected function privateFields(BaseFormRequest $request, Identity $identity): array
    {
        $email = $request->isMeApp() ? $identity->email ?: 'Geen e-mailadres' : $identity->email;
        $bsnRecord = $identity->record_bsn;

        if ($request->auth_address() === $identity->address) {
            return [
                'bsn' => !empty($bsnRecord),
                'bsn_time' => $bsnRecord ? now()->diffInSeconds($bsnRecord->created_at, true) : null,
                'email' => $email,
                ...($request->isDashboard() ? [
                    'has_identity_provider_links' => $identity->identity_provider_memberships_claimed->isNotEmpty(),
                    'can_link_identity_provider' => Gate::forUser($identity)->allows('manageLinks', [
                        IdentityProviderMembership::class,
                        $request->identityProxy(),
                    ]) && IdentityProviderConnectionQuery::whereAvailableForSelfLink(
                        IdentityProviderConnection::query(),
                        $identity,
                    )->exists(),
                ] : []),
                'profile' => $request->implementation()?->organization?->allow_profiles,
            ];
        }

        return [];
    }
}
