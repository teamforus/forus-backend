<?php

namespace App\Models;

/**
 * App\Models\FundSponsorInvestment
 *
 * @property int $id
 * @property int $fund_id
 * @property int $organization_id
 * @property float $amount
 * @property string $state
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\FundSponsor $fund_sponsor
 * @property-read string|null $created_at_locale
 * @property-read string|null $updated_at_locale
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundSponsorInvestment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundSponsorInvestment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundSponsorInvestment query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundSponsorInvestment whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundSponsorInvestment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundSponsorInvestment whereFundId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundSponsorInvestment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundSponsorInvestment whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundSponsorInvestment whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundSponsorInvestment whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class FundSponsorInvestment extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'fund_sponsor_id', 'amount'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function fund_sponsor() {
        return $this->belongsTo(FundSponsor::class);
    }
}
