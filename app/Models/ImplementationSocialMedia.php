<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\ImplementationSocialMedia
 *
 * @property int $id
 * @property int $implementation_id
 * @property string $type
 * @property string $url
 * @property string|null $title
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string $type_locale
 * @method static \Illuminate\Database\Eloquent\Builder|ImplementationSocialMedia newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ImplementationSocialMedia newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ImplementationSocialMedia query()
 * @method static \Illuminate\Database\Eloquent\Builder|ImplementationSocialMedia whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ImplementationSocialMedia whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ImplementationSocialMedia whereImplementationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ImplementationSocialMedia whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ImplementationSocialMedia whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ImplementationSocialMedia whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ImplementationSocialMedia whereUrl($value)
 * @mixin \Eloquent
 */
class ImplementationSocialMedia extends Model
{
    const TYPE_FACEBOOK = 'facebook';
    const TYPE_TWITTER = 'twitter';
    const TYPE_YOUTUBE = 'youtube';

    const TYPES = [
        self::TYPE_FACEBOOK,
        self::TYPE_TWITTER,
        self::TYPE_YOUTUBE,
    ];

    /**
     * @var string[]
     */
    protected $fillable = [
        'implementation_id', 'type', 'url', 'title',
    ];

    /**
     * @return string
     * @noinspection PhpUnused
     */
    public function getTypeLocaleAttribute(): string
    {
        return ucfirst($this->type);
    }
}
