<?php

namespace App\Models;

use App\Scopes\Builders\FundProviderQuery;
use App\Scopes\Builders\OfficeQuery;
use App\Services\MediaService\Models\Media;
use App\Services\MediaService\Traits\HasMedia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Http\Request;

/**
 * App\Models\Office
 *
 * @property int $id
 * @property int $organization_id
 * @property string $address
 * @property string|null $phone
 * @property string|null $lon
 * @property string|null $lat
 * @property string|null $postal_code
 * @property int $parsed
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|Media[] $medias
 * @property-read int|null $medias_count
 * @property-read \App\Models\Organization $organization
 * @property-read Media|null $photo
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\OfficeSchedule[] $schedules
 * @property-read int|null $schedules_count
 * @method static Builder|Office newModelQuery()
 * @method static Builder|Office newQuery()
 * @method static Builder|Office query()
 * @method static Builder|Office whereAddress($value)
 * @method static Builder|Office whereCreatedAt($value)
 * @method static Builder|Office whereId($value)
 * @method static Builder|Office whereLat($value)
 * @method static Builder|Office whereLon($value)
 * @method static Builder|Office whereOrganizationId($value)
 * @method static Builder|Office whereParsed($value)
 * @method static Builder|Office wherePhone($value)
 * @method static Builder|Office whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Office extends Model
{
    use HasMedia;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'organization_id', 'address', 'phone', 'lon', 'lat', 'postal_code', 'parsed'
    ];

    /**
     * The relations to eager load on every query.
     *
     * @var array
     */
    protected $with = [
        'schedules'
    ];

    public static function search(Request $request): Builder
    {
        /** @var Builder $query */
        $query = self::query();

        // approved only
        if ($request->input('approved', false)) {
            $query->whereHas('organization.fund_providers', static function(
                Builder $builder
            ) {
                return FundProviderQuery::whereApprovedForFundsFilter(
                    $builder,
                    Implementation::activeFundsQuery()->pluck('id')->toArray()
                );
            });
        }

        // full text search
        if ($request->has('q') && !empty($q = $request->input('q'))) {
            return OfficeQuery::queryDeepFilter($query, $q);
        }

        return $query;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(OfficeSchedule::class)->orderBy('week_day');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function organization(): BelongsTo {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get fund logo
     * @return MorphOne
     */
    public function photo(): MorphOne {
        return $this->morphOne(Media::class, 'mediable')->where([
            'type' => 'office_photo'
        ]);
    }

    /**
     * @param array|null $schedules
     * @return $this
     */
    public function updateSchedule(?array $schedules = []): self
    {
        $this->schedules()->whereNotIn('week_day', array_pluck(
            $schedules, 'week_day'
        ))->delete();

        foreach ($schedules as $schedule) {
            $this->schedules()->firstOrCreate(
                array_only($schedule, 'week_day')
            )->update(array_merge(array_fill_keys([
                'start_time', 'end_time', 'break_start_time', 'break_end_time',
            ], null), $schedule));
        }

        $this->load('schedules');

        return $this;
    }
}
