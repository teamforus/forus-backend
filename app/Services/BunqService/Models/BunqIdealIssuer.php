<?php

namespace App\Services\BunqService\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class IdealIssuer
 *
 * @property int $id
 * @property string $name
 * @property string $bic
 * @property boolean $sandbox
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @package App\Models
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
