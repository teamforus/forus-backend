<?php

namespace App\Models;

/**
 * App\Models\TokenTranslation
 *
 * @property int $id
 * @property int $token_id
 * @property string $locale
 * @property string $abbr
 * @property string $name
 * @property-read string|null $created_at_locale
 * @property-read string|null $updated_at_locale
 * @property-read \App\Models\Token $token
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TokenTranslation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TokenTranslation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TokenTranslation query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TokenTranslation whereAbbr($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TokenTranslation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TokenTranslation whereLocale($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TokenTranslation whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TokenTranslation whereTokenId($value)
 * @mixin \Eloquent
 */
class TokenTranslation extends Model
{
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'abbr', 'name'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function token() {
        return $this->belongsTo(Token::class);
    }
}
