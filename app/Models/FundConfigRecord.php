<?php

namespace App\Models;

use App\Services\Forus\Record\Models\RecordType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\FundConfigRecord
 *
 * @property int $id
 * @property int $fund_id
 * @property RecordType|null $record_type
 * @property int|null $record_validity_days
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Fund $fund
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfigRecord newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfigRecord newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfigRecord query()
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfigRecord whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfigRecord whereFundId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfigRecord whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfigRecord whereRecordType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfigRecord whereRecordValidityDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfigRecord whereUpdatedAt($value)
 * @mixin \Eloquent
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
