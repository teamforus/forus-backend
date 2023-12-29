<?php

namespace App\Http\Resources;

use App\Http\Requests\BaseFormRequest;
use App\Http\Resources\Tiny\FundTinyResource;
use App\Models\Fund;
use App\Models\Identity;
use App\Models\FundConfig;
use App\Models\Organization;
use App\Services\Forus\Auth2FAService\Models\Auth2FAProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Config;

/**
 * @property-read Identity $resource
 */
class Identity2FAStateResource extends BaseJsonResource
{
    public const LOAD = [
        'funds.fund_config',
        'funds.logo.presets',
        'identity_2fa_active',
        'employees.organization',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request): array
    {
        $request = BaseFormRequest::createFrom($request);
        $identityProxy = $request->identityProxy();
        $is2FAConfirmed = $identityProxy->is2FAConfirmed();

        return [
            'required' => $this->resource->is2FARequired(),
            'confirmed' => $is2FAConfirmed,
            'providers' => Auth2FAProviderResource::collection(Auth2FAProvider::get()),
            'provider_types' => static::getProviderTypes(),
            'active_providers' => Identity2FAResource::collection($this->resource->identity_2fa_active),
            'restrictions' => $this->getRestrictions($is2FAConfirmed, $this->resource),
            'auth_2fa_remember_ip' => $this->resource->auth_2fa_remember_ip,
            'auth_2fa_remember_hours' => Config::get('forus.auth_2fa.remember_hours'),
            'auth_2fa_forget_force' => [
                'voucher' => $this->forceForgetVoucher(),
                'organization' => $this->forceForgetOrganization(),
            ],
        ];
    }

    /**
     * @return bool
     */
    public function forceForgetVoucher(): bool
    {
        return $this->resource->funds()->where(function (Builder|Fund $builder) {
            $builder->whereHas('fund_config', function(Builder|FundConfig $builder) {
                $builder->where('auth_2fa_policy', FundConfig::AUTH_2FA_POLICY_REQUIRED);
                $builder->where('auth_2fa_remember_ip', false);
            });

            $builder->orWhereHas('fund_config', function(Builder|FundConfig $builder) {
                $builder->where('auth_2fa_policy', FundConfig::AUTH_2FA_POLICY_GLOBAL);
                $builder->whereHas('fund.organization', function(Builder|Organization $builder) {
                    $builder->where('auth_2fa_funds_policy', Organization::AUTH_2FA_FUNDS_POLICY_REQUIRED);
                    $builder->where('auth_2fa_funds_remember_ip', false);
                });
            });
        })->exists();
    }

    /**
     * @return bool
     */
    protected function forceForgetOrganization(): bool
    {
        return $this->resource->employees
            ->where('organization.auth_2fa_remember_ip', false)
            ->where('organization.auth_2fa_policy', Organization::AUTH_2FA_POLICY_REQUIRED)
            ->isNotEmpty();
    }

    /**
     * @param bool $isConfirmed
     * @param Identity $identity
     * @return array
     */
    protected function getRestrictions(bool $isConfirmed, Identity $identity): array
    {
        return [
            'emails' => $this->getRestriction($isConfirmed, $identity, 'emails'),
            'sessions' => $this->getRestriction($isConfirmed, $identity, 'sessions'),
            'reimbursements' => $this->getRestriction($isConfirmed, $identity, 'reimbursements'),
            'bi_connections' => $this->getRestriction($isConfirmed, $identity, 'bi_connections'),
        ];
    }

    /**
     * @param bool $isConfirmed
     * @param Identity $identity
     * @param string $key
     * @return array
     */
    protected function getRestriction(bool $isConfirmed, Identity $identity, string $key): array
    {
        $funds = $identity->getRestricting2FAFunds($key);

        return [
            'restricted' => $funds->isNotEmpty() && !$isConfirmed,
            'funds' => FundTinyResource::collection($funds),
        ];
    }

    /**
     * @return array[]
     */
    static function getProviderTypes(): array
    {
        return [[
            'type' => 'authenticator',
            'title' => 'Authenticator app',
            'subtitle' => 'Gebruik een authenticator app',
        ], [
            'type' => 'phone',
            'title' => 'SMS Verificatie',
            'subtitle' => 'Gebruik je telefoonnummer als verificatie',
        ]];
    }
}
