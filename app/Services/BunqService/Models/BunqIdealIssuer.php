<?php

namespace App\Services\BunqService\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Services\BunqService\Models\BunqIdealIssuer
 *
 * @property int $id
 * @property string $name
 * @property string $bic
 * @property bool $sandbox
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|BunqIdealIssuer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BunqIdealIssuer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BunqIdealIssuer query()
 * @method static \Illuminate\Database\Eloquent\Builder|BunqIdealIssuer whereBic($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BunqIdealIssuer whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BunqIdealIssuer whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BunqIdealIssuer whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BunqIdealIssuer whereSandbox($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BunqIdealIssuer whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class BunqIdealIssuer extends Model
{
    protected $fillable = [
        'name', 'bic', 'sandbox'
    ];

    protected $casts = [
        'sandbox' => 'boolean'
    ];
}
