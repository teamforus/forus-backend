<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

/**
 * App\Models\FundForm.
 *
 * @property int $id
 * @property string $name
 * @property int $fund_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Fund $fund
 * @property-read \App\Models\Organization|null $organization
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundForm newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundForm newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundForm query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundForm whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundForm whereFundId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundForm whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundForm whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundForm whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class FundForm extends Model
{
    protected $fillable = ['id', 'name', 'fund_id'];

    /**
     * @return BelongsTo
     */
    public function fund(): BelongsTo
    {
        return $this->belongsTo(Fund::class);
    }

    /**
     * @return HasOneThrough
     */
    public function organization(): HasOneThrough
    {
        return $this->hasOneThrough(Organization::class, Fund::class, 'id', 'id', 'fund_id', 'organization_id');
    }
}
