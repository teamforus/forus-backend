<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\Redirect.
 *
 * @property int $id
 * @property string|null $url
 * @property string|null $target
 * @property string|null $client_type
 * @property int|null $implementation_id
 * @property string $redirectable_type
 * @property int $redirectable_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Implementation|null $implementation
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Redirect newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Redirect newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Redirect query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Redirect whereClientType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Redirect whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Redirect whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Redirect whereImplementationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Redirect whereRedirectableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Redirect whereRedirectableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Redirect whereTarget($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Redirect whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Redirect whereUrl($value)
 * @mixin \Eloquent
 */
class Redirect extends Model
{
    protected $fillable = [
        'url', 'target', 'client_type', 'implementation_id',
    ];

    /**
     * @param array $params
     * @return string|null
     */
    public function targetUrl(array $params = []): ?string
    {
        if ($this->url) {
            return url($this->url, $params);
        }

        return $this->implementation?->urlFrontend($this->client_type, $this->getTargetUri(), array_merge([
            'target' => $this->target,
        ], $params));
    }

    /**
     * @return BelongsTo
     */
    public function implementation(): BelongsTo
    {
        return $this->belongsTo(Implementation::class);
    }

    /**
     * @return string
     */
    private function getTargetUri(): string
    {
        return '/redirect';
    }
}
