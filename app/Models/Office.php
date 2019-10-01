<?php

namespace App\Models;

use App\Services\MediaService\Models\Media;
use App\Services\MediaService\Traits\HasMedia;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Http\Request;

/**
 * Class Office
 * @property mixed $id
 * @property int $organization_id
 * @property string $address
 * @property string $phone
 * @property float $lon
 * @property float $lat
 * @property boolean $parsed
 * @property Organization $organization
 * @property Collection|OfficeSchedule[] $schedules
 * @property Media $photo
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @package App\Models
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

        if ($request->has('q') && $q = $request->input('q')) {
            $like = '%' . $q . '%';

            $query->where(
                'address','LIKE', $like
            )->orWhereHas('organization.business_type.translations', function(
                Builder $builder
            ) use ($like) {
                $builder->where('business_type_translations.name', 'LIKE', $like);
            })->orWhereHas('organization', function(Builder $builder) use ($like) {
                $builder->where('organizations.name', 'LIKE', $like);
            })->orWhereHas('organization', function(Builder $builder) use ($like) {
                $builder->where('organizations.email_public', 1);
                $builder->where('organizations.email', 'LIKE', $like);
            })->orWhereHas('organization', function(Builder $builder) use ($like) {
                $builder->where('organizations.phone_public', 1);
                $builder->where('organizations.phone', 'LIKE', $like);
            })->orWhereHas('organization', function(Builder $builder) use ($like) {
                $builder->where('organizations.website_public', 1);
                $builder->where('organizations.website', 'LIKE', $like);
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
