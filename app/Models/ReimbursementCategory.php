<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * App\Models\ReimbursementCategory
 *
 * @property int $id
 * @property int $organization_id
 * @property string $name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Organization $organization
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Reimbursement> $reimbursements
 * @property-read int|null $reimbursements_count
 * @method static \Illuminate\Database\Eloquent\Builder|ReimbursementCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ReimbursementCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ReimbursementCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder|ReimbursementCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReimbursementCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReimbursementCategory whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReimbursementCategory whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReimbursementCategory whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ReimbursementCategory extends Model
{
    /**
     * @var string[]
     */
    protected $fillable = [
        'name',
    ];

    /**
     * @return HasMany
     * @noinspection PhpUnused
     */
    public function reimbursements(): HasMany
    {
        return $this->hasMany(Reimbursement::class);
    }

    /**
     * @return BelongsTo
     * @noinspection PhpUnused
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
