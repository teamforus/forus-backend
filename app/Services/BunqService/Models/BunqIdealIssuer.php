<?php

namespace App\Services\BunqService\Models;

use Carbon\Carbon;
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
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\BunqService\Models\BunqIdealIssuer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\BunqService\Models\BunqIdealIssuer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\BunqService\Models\BunqIdealIssuer query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\BunqService\Models\BunqIdealIssuer whereBic($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\BunqService\Models\BunqIdealIssuer whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\BunqService\Models\BunqIdealIssuer whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\BunqService\Models\BunqIdealIssuer whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\BunqService\Models\BunqIdealIssuer whereSandbox($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\BunqService\Models\BunqIdealIssuer whereUpdatedAt($value)
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
