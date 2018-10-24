<?php

namespace App\Models;

use Carbon\Carbon;

/**
 * Class ProviderIdentity
 * @property mixed $id
 * @property integer $provider_id
 * @property integer $product_category_id
 * @property string $identity_address
 * @property Organization $organization
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @package App\Models
 */
class ProviderIdentity extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'provider_id', 'identity_address'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function organization() {
        return $this->belongsTo(Organization::class, 'provider_id');
    }
}
