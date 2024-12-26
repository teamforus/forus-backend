<?php

namespace App\Models;

use App\Services\MediaService\Models\Media;
use App\Services\MediaService\Traits\HasMedia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * App\Models\Office
 *
 * @property int $id
 * @property int $organization_id
 * @property string $address
 * @property string|null $phone
 * @property string|null $branch_id
 * @property string|null $branch_name
 * @property string|null $branch_number
 * @property string|null $lon
 * @property string|null $lat
 * @property string|null $postcode
 * @property string|null $postcode_number
 * @property string|null $postcode_addition
 * @property int $parsed
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Employee[] $employees
 * @property-read int|null $employees_count
 * @property-read string $branch_full_name
 * @property-read \Illuminate\Database\Eloquent\Collection|Media[] $medias
 * @property-read int|null $medias_count
 * @property-read \App\Models\Organization $organization
 * @property-read Media|null $photo
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\OfficeSchedule[] $schedules
 * @property-read int|null $schedules_count
 * @method static Builder<static>|Office newModelQuery()
 * @method static Builder<static>|Office newQuery()
 * @method static Builder<static>|Office query()
 * @method static Builder<static>|Office whereAddress($value)
 * @method static Builder<static>|Office whereBranchId($value)
 * @method static Builder<static>|Office whereBranchName($value)
 * @method static Builder<static>|Office whereBranchNumber($value)
 * @method static Builder<static>|Office whereCreatedAt($value)
 * @method static Builder<static>|Office whereId($value)
 * @method static Builder<static>|Office whereLat($value)
 * @method static Builder<static>|Office whereLon($value)
 * @method static Builder<static>|Office whereOrganizationId($value)
 * @method static Builder<static>|Office whereParsed($value)
 * @method static Builder<static>|Office wherePhone($value)
 * @method static Builder<static>|Office wherePostcode($value)
 * @method static Builder<static>|Office wherePostcodeAddition($value)
 * @method static Builder<static>|Office wherePostcodeNumber($value)
 * @method static Builder<static>|Office whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Office extends BaseModel
{
    use HasMedia;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'organization_id', 'address', 'phone', 'lon', 'lat', 'parsed',
        'postcode', 'postcode_number', 'postcode_addition',
        'branch_name', 'branch_number', 'branch_id',
    ];

    /**
     * The relations to eager load on every query.
     *
     * @var array
     */
    protected $with = [];

    /**
     * @var int
     */
    protected $perPage = 100;

    /**
     * @return string
     * @noinspection PhpUnused
     */
    public function getBranchFullNameAttribute(): string
    {
        return trim(implode(', ', array_filter([
            $this->branch_name, $this->branch_number, $this->branch_id,
        ])));
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(OfficeSchedule::class)->orderBy('week_day');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
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

    /**
     * Update postcode and coordinates by google api
     */
    public function updateGeoData(): void
    {
        $geocodeService = resolve('geocode_api');
        $location = $geocodeService->getLocation($this->address);

        if (is_array($location)) {
            $location['lon'] = $location['lng'] ?? null;
            $this->update($location);
        }
    }
}
