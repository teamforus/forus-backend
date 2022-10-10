<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * App\Models\Bookmark
 *
 * @property int $id
 * @property string $identity_address
 * @property string $bookmarkable_type
 * @property int $bookmarkable_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Model|\Eloquent $bookmarkable
 * @property-read \App\Models\Identity $identity
 * @method static \Illuminate\Database\Eloquent\Builder|Bookmark newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Bookmark newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Bookmark query()
 * @method static \Illuminate\Database\Eloquent\Builder|Bookmark whereBookmarkableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Bookmark whereBookmarkableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Bookmark whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Bookmark whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Bookmark whereIdentityAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Bookmark whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Bookmark extends Model
{
    use HasFactory;

    protected $fillable = [
        'identity_address', 'bookmarkable_id', 'bookmarkable_type',
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
     * @noinspection PhpUnused
     */
    public function bookmarkable() : MorphTo
    {
        return $this->morphTo();
    }
}
