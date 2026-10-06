<?php

namespace App\Models;

use App\Enums\NodeType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $decision_tree_id
 * @property string $key
 * @property NodeType $type
 * @property string $label
 * @property string|null $description
 * @property bool|null $eligible
 * @property bool $show_button
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\DecisionOption[] $options
 * @property-read int|null $options_count
 * @property-read \App\Models\DecisionTree $tree
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DecisionNode newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DecisionNode newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DecisionNode query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DecisionNode whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DecisionNode whereDecisionTreeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DecisionNode whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DecisionNode whereEligible($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DecisionNode whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DecisionNode whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DecisionNode whereLabel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DecisionNode whereShowButton($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DecisionNode whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DecisionNode whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DecisionNode whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class DecisionNode extends Model
{
    /**
     * @var string[]
     */
    protected $fillable = [
        'decision_tree_id', 'key', 'type', 'label', 'description',
        'eligible', 'show_button', 'button_url', 'sort_order',
    ];

    protected $casts = [
        'type' => NodeType::class,
        'eligible' => 'boolean',
        'show_button' => 'boolean',
    ];


    /**
     * @return BelongsTo
     */
    public function tree(): BelongsTo
    {
        return $this->belongsTo(DecisionTree::class, 'decision_tree_id');
    }

    /**
     * @return HasMany
     */
    public function options(): HasMany
    {
        return $this->hasMany(DecisionOption::class)->orderBy('sort_order')->orderBy('id');
    }
}
