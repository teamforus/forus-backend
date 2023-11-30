<?php

namespace App\Services\MollieService\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Services\MollieService\Models\MollieConnectionProfile
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $phone
 * @property string $website
 * @property string|null $mollie_id
 * @property string $state
 * @property bool $current
 * @property int $mollie_connection_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Services\MollieService\Models\MollieConnection $mollie_connection
 * @method static \Illuminate\Database\Eloquent\Builder|MollieConnectionProfile newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MollieConnectionProfile newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MollieConnectionProfile query()
 * @method static \Illuminate\Database\Eloquent\Builder|MollieConnectionProfile whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MollieConnectionProfile whereCurrent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MollieConnectionProfile whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MollieConnectionProfile whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MollieConnectionProfile whereMollieConnectionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MollieConnectionProfile whereMollieId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MollieConnectionProfile whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MollieConnectionProfile wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MollieConnectionProfile whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MollieConnectionProfile whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MollieConnectionProfile whereWebsite($value)
 * @mixin \Eloquent
 */
class MollieConnectionProfile extends BaseModel
{
    public const STATE_ACTIVE = 'active';
    public const STATE_PENDING = 'pending';

    /**
     * @var string[]
     */
    protected $fillable = [
        'name', 'email', 'phone', 'state', 'website', 'current', 'mollie_id',
        'mollie_connection_id',
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'current' => 'boolean',
    ];

    /**
     * @noinspection PhpUnused
     * @return BelongsTo
     */
    public function mollie_connection(): BelongsTo
    {
        return $this->belongsTo(MollieConnection::class);
    }
}
