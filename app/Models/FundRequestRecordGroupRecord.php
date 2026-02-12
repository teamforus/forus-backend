<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int|null $fund_request_record_group_id
 * @property string $record_type_key
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundRequestRecordGroupRecord newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundRequestRecordGroupRecord newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundRequestRecordGroupRecord query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundRequestRecordGroupRecord whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundRequestRecordGroupRecord whereFundRequestRecordGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundRequestRecordGroupRecord whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundRequestRecordGroupRecord whereRecordTypeKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundRequestRecordGroupRecord whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class FundRequestRecordGroupRecord extends Model
{
    protected $fillable = [
        'record_type_key', 'fund_request_record_group_id',
    ];
}
