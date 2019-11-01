<?php

namespace App\Models;

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
 * @property-read string|null $created_at_locale
 * @property-read string|null $updated_at_locale
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

        if ($approved = $request->input('approved', false)) {
            $query->whereIn('organization_id', FundProvider::whereIn(
                'fund_id', Implementation::activeFundsQuery()->pluck('id')
            )->where([
                'state' => FundProvider::STATE_APPROVED
            ])->pluck('organization_id')->unique()->values()->toArray());
        }

        if ($request->has('q') && $q = $request->input('q')) {
            $like = '%' . $q . '%';

            $query->where(function(Builder $query) use ($like) {
                $query->where(
                    'address','LIKE', $like
                )->orWhereHas('organization.business_type.translations', function(
                    Builder $builder
                ) use ($like) {
                    $builder->where('business_type_translations.name', 'LIKE', $like);
                })->orWhereHas('organization', function(Builder $builder) use ($like) {
                    $builder->where('organizations.name', 'LIKE', $like);
                })->orWhereHas('organization', function(Builder $builder) use ($like) {
                    $builder->where('organizations.email_public', true);
                    $builder->where('organizations.email', 'LIKE', $like);
                })->orWhereHas('organization', function(Builder $builder) use ($like) {
                    $builder->where('organizations.phone_public', true);
                    $builder->where('organizations.phone', 'LIKE', $like);
                })->orWhereHas('organization', function(Builder $builder) use ($like) {
                    $builder->where('organizations.website_public', true);
                    $builder->where('organizations.website', 'LIKE', $like);
                });
            });
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
