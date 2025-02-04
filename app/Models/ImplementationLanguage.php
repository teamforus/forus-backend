<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\ImplementationLanguage.
 *
 * @property int $id
 * @property int $implementation_id
 * @property int $language_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Implementation $implementation
 * @property-read \App\Models\Language $language
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImplementationLanguage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImplementationLanguage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImplementationLanguage query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImplementationLanguage whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImplementationLanguage whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImplementationLanguage whereImplementationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImplementationLanguage whereLanguageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImplementationLanguage whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ImplementationLanguage extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'implementation_languages';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'language_id',
        'implementation_id',
    ];

    /**
     * Get the implementation associated with this record.
     */
    public function implementation(): BelongsTo
    {
        return $this->belongsTo(Implementation::class);
    }

    /**
     * Get the language associated with this record.
     */
    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }
}
