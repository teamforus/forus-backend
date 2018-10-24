<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

/**
 * Class Prevalidation
 * @property int $id
 * @property string $uid
 * @property string $identity_address
 * @property string $state
 * @property Collection $records
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @package App\Models
 */
class Prevalidation extends Model
{
    /**
     * @var array
     */
    protected $fillable = [
        'uid', 'identity_address', 'state'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function records() {
        return $this->hasMany(PrevalidationRecord::class);
    }
}
