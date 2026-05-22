<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int|null $fund_request_record_group_id
 * @property string $record_type_key
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecordGroupKey newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecordGroupKey newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecordGroupKey query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecordGroupKey whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecordGroupKey whereFundRequestRecordGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecordGroupKey whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecordGroupKey whereRecordTypeKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecordGroupKey whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class RecordGroupKey extends Model
{
    protected $fillable = [
        'record_type_key', 'fund_request_record_group_id',
    ];
}
