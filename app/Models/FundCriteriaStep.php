<?php

namespace App\Models;

use App\Services\TranslationService\Traits\HasOnDemandTranslations;
use App\Traits\HasMarkdownFields;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * App\Models\FundCriteriaStep.
 *
 * @property int $id
 * @property string $title
 * @property string|null $description
 * @property int $fund_id
 * @property int $order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Fund $fund
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\FundCriterion[] $fund_criteria
 * @property-read int|null $fund_criteria_count
 * @property-read string $description_html
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Services\TranslationService\Models\TranslationValue[] $translation_values
 * @property-read int|null $translation_values_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundCriteriaStep newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundCriteriaStep newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundCriteriaStep query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundCriteriaStep whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundCriteriaStep whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundCriteriaStep whereFundId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundCriteriaStep whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundCriteriaStep whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundCriteriaStep whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundCriteriaStep whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class FundCriteriaStep extends Model
{
    use HasMarkdownFields;
    use HasOnDemandTranslations;

    /**
     * @return BelongsTo
     */
    public function fund(): BelongsTo
    {
        return $this->belongsTo(Fund::class);
    }

    /**
     * @return HasMany
     */
    public function fund_criteria(): HasMany
    {
        return $this->hasMany(FundCriterion::class);
    }
}
