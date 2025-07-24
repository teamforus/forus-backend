<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 *
 *
 * @property int $id
 * @property int $profile_id
 * @property string $name
 * @property string $iban
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Profile|null $profile
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileBankAccount newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileBankAccount newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileBankAccount query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileBankAccount whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileBankAccount whereIban($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileBankAccount whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileBankAccount whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileBankAccount whereProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileBankAccount whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ProfileBankAccount extends Model
{
    protected $fillable = [
        'profile_id', 'name', 'iban',
    ];

    /**
     * @return BelongsTo
     */
    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}
