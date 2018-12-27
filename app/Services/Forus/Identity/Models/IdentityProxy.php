<?php

namespace App\Services\Forus\Identity\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class IdentityProxy
 * @property mixed $id
 * @property integer $identity_id
 * @property string $access_token
 * @property string $exchange_token
 * @property string $state
 * @property integer $expires_in
 * @property Identity $identity
 * @property bool $exchange_time_expired
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @package App\Models
 */
class IdentityProxy extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'identity_address', 'access_token', 'exchange_token', 'state', 'type',
        'expires_in'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function identity() {
        return $this->belongsTo(Identity::class, 'identity_address', 'address');
    }

    /**
     * Activation time expired
     *
     * @return bool
     */
    public function getExchangeTimeExpiredAttribute() {
        return $this->created_at->addSeconds($this->expires_in)->isPast();
    }
}
