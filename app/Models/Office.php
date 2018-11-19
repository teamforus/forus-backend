<?php

namespace App\Models;

use App\Services\MediaService\Models\Media;
use App\Services\MediaService\Traits\HasMedia;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphOne;

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
 * @property Collection $schedules
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
