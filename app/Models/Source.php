<?php

namespace App\Models;

/**
 * App\Models\Source
 *
 * @property int $id
 * @property string $key
 * @property string|null $url
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string|null $created_at_locale
 * @property-read string|null $updated_at_locale
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Source newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Source newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Source query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Source whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Source whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Source whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Source whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Source whereUrl($value)
 * @mixin \Eloquent
 */
class Source extends Model
{
    protected $fillable = [
        "key", "url"
    ];
}
