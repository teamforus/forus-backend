<?php

namespace App\Models;

use App\Scopes\Builders\FundProviderQuery;
use App\Scopes\Builders\OfficeQuery;
use App\Services\MediaService\Models\Media;
use App\Services\MediaService\Traits\HasMedia;
use Illuminate\Database\Eloquent\Builder;
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
 * @property int $parsed
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Services\MediaService\Models\Media[] $medias
 * @property-read int|null $medias_count
 * @property-read \App\Models\Organization $organization
 * @property-read \App\Services\MediaService\Models\Media $photo
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\OfficeSchedule[] $schedules
 * @property-read int|null $schedules_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Office newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Office newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Office query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Office whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Office whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Office whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Office whereLat($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Office whereLon($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Office whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Office whereParsed($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Office wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Office whereUpdatedAt($value)
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
        'organization_id', 'address', 'phone', 'lon', 'lat', 'parsed'
    ];

    /**
     * The relations to eager load on every query.
     *
     * @var array
     */
    protected $with = [
        'schedules'
    ];

    public static function search(Request $request)
    {
        $query = self::query();

        // approved only
        if ($request->input('approved', false)) {
            $query->whereHas('organization.fund_providers', function(Builder $builder) {
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
    public function schedules()
    {
        return $this->hasMany(OfficeSchedule::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function organization() {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get fund logo
     * @return MorphOne
     */
    public function photo() {
        return $this->morphOne(Media::class, 'mediable')->where([
            'type' => 'office_photo'
        ]);
    }
}
