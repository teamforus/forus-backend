<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\FundFormula
 *
 * @property int $id
 * @property int $fund_id
 * @property string $type
 * @property string $amount
 * @property string|null $record_type_key
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Fund $fund
 * @property-read string|null $amount_locale
 * @property-read string|null $type_locale
 * @property-read \App\Models\RecordType|null $record_type
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundFormula newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundFormula newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundFormula query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundFormula whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundFormula whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundFormula whereFundId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundFormula whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundFormula whereRecordTypeKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundFormula whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundFormula whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class FundFormula extends BaseModel
{
    protected $fillable = [
        'id', 'fund_id', 'type', 'amount', 'record_type_key',
    ];

    /**
     * @return string|null
     * @noinspection PhpUnused
     */
    public function getAmountLocaleAttribute(): ?string
    {
        return currency_format_locale($this->amount);
    }

    /**
     * @return string|null
     * @noinspection PhpUnused
     */
    public function getTypeLocaleAttribute(): ?string
    {
        return $this->type === 'fixed' ? 'Vastgesteld' : 'Multiply';
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function fund(): BelongsTo
    {
        return $this->belongsTo(Fund::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     * @noinspection PhpUnused
     */
    public function record_type(): BelongsTo
    {
        return $this->belongsTo(RecordType::class, 'record_type_key', 'key');
    }
}
