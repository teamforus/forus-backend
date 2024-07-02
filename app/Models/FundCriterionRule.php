<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\FundCriterionRule
 *
 * @property int $id
 * @property int $fund_criterion_id
 * @property string $record_type_key
 * @property string $operator
 * @property string $value
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\FundCriterion $fund_criterion
 * @property-read \App\Models\RecordType $record_type
 * @method static \Illuminate\Database\Eloquent\Builder|FundCriterionRule newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FundCriterionRule newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FundCriterionRule query()
 * @method static \Illuminate\Database\Eloquent\Builder|FundCriterionRule whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundCriterionRule whereFundCriterionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundCriterionRule whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundCriterionRule whereOperator($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundCriterionRule whereRecordTypeKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundCriterionRule whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundCriterionRule whereValue($value)
 * @mixin \Eloquent
 */
class FundCriterionRule extends Model
{
    /**
     * @return BelongsTo
     * @noinspection PhpUnused
     */
    public function fund_criterion(): BelongsTo
    {
        return $this->belongsTo(FundCriterion::class);
    }

    /**
     * @return BelongsTo
     * @noinspection PhpUnused
     */
    public function record_type(): BelongsTo
    {
        return $this->belongsTo(RecordType::class, 'record_type_key', 'key');
    }
}
