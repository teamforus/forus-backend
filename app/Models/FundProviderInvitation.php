<?php

namespace App\Models;

use App\Events\Funds\FundProviderInvitedEvent;
use App\Scopes\Builders\FundProviderQuery;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
 * @property-read \App\Models\Fund|null $from_fund
 * @property-read \App\Models\Fund $fund
 * @property-read Carbon $expire_at
 * @property-read bool $expired
 * @property-read \App\Models\Organization $organization
 * @method static Builder|FundProviderInvitation newModelQuery()
 * @method static Builder|FundProviderInvitation newQuery()
 * @method static Builder|FundProviderInvitation query()
 * @method static Builder|FundProviderInvitation whereAllowBudget($value)
 * @method static Builder|FundProviderInvitation whereAllowProducts($value)
 * @method static Builder|FundProviderInvitation whereCreatedAt($value)
 * @method static Builder|FundProviderInvitation whereFromFundId($value)
 * @method static Builder|FundProviderInvitation whereFundId($value)
 * @method static Builder|FundProviderInvitation whereId($value)
 * @method static Builder|FundProviderInvitation whereOrganizationId($value)
 * @method static Builder|FundProviderInvitation whereState($value)
 * @method static Builder|FundProviderInvitation whereToken($value)
 * @method static Builder|FundProviderInvitation whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class FundProviderInvitation extends Model
{
    public const STATE_ACCEPTED = 'accepted';
    public const STATE_EXPIRED = 'expired';
    public const STATE_PENDING = 'pending';

    public const STATES = [
        self::STATE_ACCEPTED,
        self::STATE_PENDING,
        self::STATE_EXPIRED,
    ];

    // expires in 2 weeks
    public const VALIDITY_IN_MINUTES = 14 * 24 * 60;

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
    public function fund(): BelongsTo
    {
        return $this->belongsTo(Fund::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     * @noinspection PhpUnused
     */
    public function from_fund(): BelongsTo
    {
        return $this->belongsTo(Fund::class, 'from_fund_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @param Fund $fundFrom
     * @param Fund $fundTo
     * @return Builder|Collection
     */
    public static function inviteFromFundToFund(Fund $fundFrom, Fund $fundTo): Collection
    {
        $alreadyProviders = $fundTo->provider_organizations_approved->pluck('id');
        $alreadyInvited = $fundFrom->provider_invitations()->where([
            'state' => self::STATE_PENDING
        ])->pluck('organization_id');

        $skipProviders = $alreadyProviders->merge($alreadyInvited)->toArray();

        $providers = $fundFrom->providers()->where(function(Builder $builder) use ($fundFrom) {
            FundProviderQuery::whereApprovedForFundsFilter($builder, $fundFrom->id);
        })->whereNotIn('organization_id', $skipProviders)->get();

        return $providers->map(function (FundProvider $provider) use ($fundFrom, $fundTo) {
            /** @var FundProviderInvitation $providerInvitation */
            $providerInvitation = $fundFrom->provider_invitations()->create([
                'token'             => token_generator()->generate(200),
                'fund_id'           => $fundTo->id,
                'organization_id'   => $provider->organization_id,
                'state'             => self::STATE_PENDING,
                'allow_budget'      => $provider->allow_budget,
                'allow_products'    => $provider->allow_products || $provider->allow_some_products
            ]);

            FundProviderInvitedEvent::dispatch($fundTo, $providerInvitation);

            return $providerInvitation;
        });
    }

    /**
     * Invitation is expired
     *
     * @return bool
     */
    public function getExpiredAttribute(): bool
    {
        return $this->created_at->lte(
            now()->subMinutes(self::VALIDITY_IN_MINUTES)
        ) || $this->state === self::STATE_EXPIRED;
    }

    /**
     * Date when invitation will expire
     *
     * @return \Carbon\Carbon
     */
    public function getExpireAtAttribute(): Carbon
    {
        return $this->created_at->addMinutes(self::VALIDITY_IN_MINUTES);
    }

    /**
     * @return FundProviderInvitation
     */
    public function accept(): FundProviderInvitation
    {
        $this->fund->providers()->firstOrCreate([
            'organization_id' => $this->organization_id,
        ])->update($this->only('allow_products', 'allow_budget'));

        return $this->updateModel([
            'state' => self::STATE_ACCEPTED
        ]);
    }
}
