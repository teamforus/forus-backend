<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int|null $record_group_id
 * @property string $record_type_key
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecordGroupRecordType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecordGroupRecordType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecordGroupRecordType query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecordGroupRecordType whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecordGroupRecordType whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecordGroupRecordType whereRecordGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecordGroupRecordType whereRecordTypeKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecordGroupRecordType whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class RecordGroupRecordType extends Model
{
    protected $fillable = [
        'record_type_key', 'record_group_id',
    ];
}
