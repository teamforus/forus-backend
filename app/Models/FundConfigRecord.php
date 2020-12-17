<?php

namespace App\Models;

use App\Services\Forus\Record\Models\RecordType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class FundConfigRecord
 *
 * @package App\Models
 * @property int $id
 * @property int $fund_id
 * @property int $record_type_id
 * @property int|null $record_validity_days
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Fund $fund
 * @property-read \App\Services\Forus\Record\Models\RecordType $record_type
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundConfigRecord newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundConfigRecord newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundConfigRecord query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundConfigRecord whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundConfigRecord whereFundId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundConfigRecord whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundConfigRecord whereRecordTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundConfigRecord whereRecordValidityDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundConfigRecord whereUpdatedAt($value)
 * @mixin \Eloquent
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundConfigRecord whereRecordType($value)
 */
class FundConfigRecord extends Model
{
    protected $fillable = [
        'fund_id', 'record_type_id', 'record_validity_days'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     * @noinspection PhpUnused
     */
    public function fund(): BelongsTo {
        return $this->belongsTo(Fund::class);
    }

    /**
     * @return BelongsTo
     * @noinspection PhpUnused
     */
    public function record_type(): BelongsTo {
        return $this->belongsTo(RecordType::class);
    }
}
