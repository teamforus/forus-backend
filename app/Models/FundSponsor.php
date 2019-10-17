<?php

namespace App\Models;

/**
 * App\Models\FundSponsor
 *
 * @property-read \App\Models\Fund $fund
 * @property-read string|null $created_at_locale
 * @property-read string|null $updated_at_locale
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\FundSponsorInvestment[] $investments
 * @property-read int|null $investments_count
 * @property-read \App\Models\Organization $sponsor
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundSponsor newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundSponsor newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundSponsor query()
 * @mixin \Eloquent
 */
class FundSponsor extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'fund_id', 'sponsor_id', 'state'
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
    public function sponsor() {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function investments() {
        return $this->hasMany(FundSponsorInvestment::class);
    }
}
