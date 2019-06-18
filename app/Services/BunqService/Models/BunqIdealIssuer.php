<?php

namespace App\Services\BunqService\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class IdealIssuer
 * @property int $id
 * @property string $name
 * @property string $bic
 * @property boolean $sandbox
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @package App\Models
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
