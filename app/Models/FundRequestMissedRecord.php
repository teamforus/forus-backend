<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $type
 * @property string $group
 * @property string $field
 * @property int $fund_request_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundRequestMissedRecord newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundRequestMissedRecord newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundRequestMissedRecord query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundRequestMissedRecord whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundRequestMissedRecord whereField($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundRequestMissedRecord whereFundRequestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundRequestMissedRecord whereGroup($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundRequestMissedRecord whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundRequestMissedRecord whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundRequestMissedRecord whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class FundRequestMissedRecord extends Model
{
    public const string TYPE_INFO = 'info';
    public const string TYPE_WARNING = 'warning';

    protected $fillable = [
        'fund_request_id', 'group', 'field', 'type',
    ];
}
