<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\NotificationTemplate
 *
 * @property int $id
 * @property string $key
 * @property string $type
 * @property int $formal
 * @property int $implementation_id
 * @property string $title
 * @property string $content
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Implementation $implementation
 * @method static \Illuminate\Database\Eloquent\Builder|NotificationTemplate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|NotificationTemplate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|NotificationTemplate query()
 * @method static \Illuminate\Database\Eloquent\Builder|NotificationTemplate whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|NotificationTemplate whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|NotificationTemplate whereFormal($value)
 * @method static \Illuminate\Database\Eloquent\Builder|NotificationTemplate whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|NotificationTemplate whereImplementationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|NotificationTemplate whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|NotificationTemplate whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|NotificationTemplate whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|NotificationTemplate whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class NotificationTemplate extends Model
{
    protected $fillable = [
        'key', 'type', 'formal', 'title', 'content', 'implementation_id',
    ];

    /**
     * @return BelongsTo
     */
    public function implementation(): BelongsTo
    {
        return $this->belongsTo(Implementation::class);
    }

    /**
     * @return string|null
     */
    public function convertToHtml(): ?string
    {
        return resolve('markdown')->convertToHtml($this->content);
    }
}
