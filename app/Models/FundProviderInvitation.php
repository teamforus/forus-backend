<?php

namespace App\Models;

/**
 * App\Models\FundProviderInvitation
 *
 * @property int $id
 * @property int $organization_id
 * @property int $from_fund_id
 * @property int $fund_id
 * @property bool $allow_budget
 * @property bool $allow_products
 * @property string $state
 * @property string $token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Fund $from_fund
 * @property-read \App\Models\Fund $fund
 * @property-read string|null $created_at_locale
 * @property-read \Illuminate\Support\Carbon $expire_at
 * @property-read bool $expired
 * @property-read string|null $updated_at_locale
 * @property-read \App\Models\Organization $organization
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundProviderInvitation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundProviderInvitation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundProviderInvitation query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundProviderInvitation whereAllowBudget($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundProviderInvitation whereAllowProducts($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundProviderInvitation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundProviderInvitation whereFromFundId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundProviderInvitation whereFundId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundProviderInvitation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundProviderInvitation whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundProviderInvitation whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundProviderInvitation whereToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundProviderInvitation whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class FundProviderInvitation extends Model
{
    const STATE_ACCEPTED = 'accepted';
    const STATE_EXPIRED = 'expired';
    const STATE_PENDING = 'pending';

    const STATES = [
        self::STATE_ACCEPTED,
        self::STATE_PENDING,
        self::STATE_EXPIRED,
    ];

    // expires in 2 weeks
    const VALIDITY_IN_MINUTES = 14 * 24 * 60;

    protected $fillable = [
        'organization_id', 'from_fund_id', 'fund_id', 'state', 'token',
        'allow_budget', 'allow_products',
    ];

    protected $casts = [
        'allow_budget' => 'boolean',
        'allow_products' => 'boolean',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function fund() {
        return $this->belongsTo(Fund::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function from_fund() {
        return $this->belongsTo(Fund::class, 'from_fund_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function organization() {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @param Fund $fromFund
     * @param Fund $fund
     * @return \Illuminate\Support\Collection
     * @throws \Exception
     */
    public static function inviteFromFundToFund(Fund $fromFund, Fund $fund)
    {
        $recordRepo = resolve('forus.services.record');
        $token_generator = resolve('token_generator');
        $notificationService = resolve('forus.services.notification');

        $alreadyProviders = $fund->provider_organizations_approved->pluck('id');
        $alreadyInvited = $fromFund->provider_invitations()->where([
            'state' => FundProviderInvitation::STATE_PENDING
        ])->pluck('organization_id');

        $skipProviders = $alreadyProviders->merge($alreadyInvited)->toArray();

        return $fromFund->providers_approved()->whereNotIn(
            'organization_id', $skipProviders
        )->get()->map(function (FundProvider $provider) use (
            $fromFund, $fund, $token_generator, $recordRepo, $notificationService
        ) {
            /** @var FundProviderInvitation $providerInvitation */
            $providerInvitation = $fromFund->provider_invitations()->create([
                'token'             => $token_generator->generate(200),
                'fund_id'           => $fund->id,
                'organization_id'   => $provider->organization_id,
                'state'             => self::STATE_PENDING,
                'allow_budget'      => $provider->allow_budget,
                'allow_products'    => $provider->allow_products ||
                    $provider->allow_some_products
            ]);

            $notificationService->providerInvited(
                $recordRepo->primaryEmailByAddress(
                    $providerInvitation->organization->identity_address
                ),
                $providerInvitation->organization->name,
                $providerInvitation->fund->organization->name,
                $providerInvitation->fund->organization->phone,
                $providerInvitation->fund->organization->email,
                $providerInvitation->fund->name,
                format_date_locale($providerInvitation->fund->start_date),
                format_date_locale($providerInvitation->fund->end_date),
                $providerInvitation->from_fund->name,
                $fromFund->fund_config->implementation->urlProviderDashboard(sprintf(
                    '/provider-invitations/%s', $providerInvitation->token
                ))
            );

            return $providerInvitation;
        });
    }

    /**
     * Invitation is expired
     *
     * @return bool
     */
    public function getExpiredAttribute() {
        return $this->created_at->lte(
            now()->subMinutes(self::VALIDITY_IN_MINUTES)
        ) || $this->state == self::STATE_EXPIRED;
    }

    /**
     * Date when invitation will expire
     *
     * @return \Illuminate\Support\Carbon
     */
    public function getExpireAtAttribute() {
        return $this->created_at->addMinutes(self::VALIDITY_IN_MINUTES);
    }

    /**
     * @return FundProviderInvitation
     */
    public function accept() {
        $this->fund->providers()->firstOrCreate([
            'organization_id' => $this->organization_id,
        ])->update($this->only([
            'allow_products', 'allow_budget'
        ]));

        return $this->updateModel([
            'state' => self::STATE_ACCEPTED
        ]);
    }
}
