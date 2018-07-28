<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Office
 * @property mixed $id
 * @property int $organization_id
 * @property string $address
 * @property string $phone
 * @property string $email
 * @property float $lon
 * @property float $lat
 * @property boolean $parsed
 * @property Organization $organization
 * @property Collection $schedules
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @package App\Models
 */
class Office extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'organization_id', 'address', 'phone', 'email', 'lon', 'lat',
        'parsed'
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
}
