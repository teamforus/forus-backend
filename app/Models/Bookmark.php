<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * App\Models\BusinessType
 *
 * @property int $id
 * @property string $key
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Model|\Eloquent $bookmarkable
 * @mixin \Eloquent
 */
class Bookmark extends Model
{
    use HasFactory;

    protected $fillable = [
        'identity_address', 'bookmarkable_id', 'bookmarkable_type'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function identity(): BelongsTo
    {
        return $this->belongsTo(Identity::class, 'identity_address', 'address');
    }

    /**
     * @return MorphTo
     */
    public function bookmarkable() : MorphTo
    {
        return $this->morphTo();
    }
}
