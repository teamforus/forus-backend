<?php

namespace App\Models;

use Carbon\Carbon;

/**
 * Class Validator
 * @property mixed $id
 * @property mixed $organization_id
 * @property string $identity_address
 * @property string $key
 * @property string $name
 * @property Organization $organization
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @package App\Models
 */
class Validator extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'identity_address', 'key', 'name', 'organization_id'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function organization() {
        return $this->belongsTo(Organization::class);
    }
}
