<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\ImplementationPage
 *
 * @property int $id
 * @property int $implementation_id
 * @property string|null $page_type
 * @property string|null $content
 * @property string|null $external_url
 * @property bool $external
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string $content_html
 * @property-read \App\Models\Implementation $implementation
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ImplementationPage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ImplementationPage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ImplementationPage query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ImplementationPage whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ImplementationPage whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ImplementationPage whereExternal($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ImplementationPage whereExternalUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ImplementationPage whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ImplementationPage whereImplementationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ImplementationPage wherePageType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ImplementationPage whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ImplementationPage extends Model
{
    const TYPE_EXPLANATION = 'explanation';
    const TYPE_PROVIDER = 'provider';
    const TYPE_PRIVACY = 'privacy';
    const TYPE_ACCESSIBILITY = 'accessibility';
    const TYPE_TERMS_AND_CONDITIONS = 'terms_and_conditions';
    const TYPE_FOOTER_OPENING_TIMES = 'footer_opening_times';
    const TYPE_FOOTER_CONTACT_DETAILS = 'footer_contact_details';

    const TYPES = [
        self::TYPE_PROVIDER,
        self::TYPE_PRIVACY,
        self::TYPE_EXPLANATION,
        self::TYPE_ACCESSIBILITY,
        self::TYPE_TERMS_AND_CONDITIONS,
        self::TYPE_FOOTER_CONTACT_DETAILS,
        self::TYPE_FOOTER_OPENING_TIMES,
    ];

    const TYPES_INTERNAL = [
        self::TYPE_PROVIDER,
        self::TYPE_FOOTER_OPENING_TIMES,
        self::TYPE_FOOTER_CONTACT_DETAILS,
    ];

    /**
     * @var string[]
     */
    protected $fillable = [
        'implementation_id', 'page_type', 'content', 'external', 'external_url',
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'external' => 'bool',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function implementation(): BelongsTo
    {
        return $this->belongsTo(Implementation::class);
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    public function getContentHtmlAttribute(): string
    {
        return resolve('markdown')->convertToHtml($this->content ?? '');
    }
}
