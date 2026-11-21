<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $decision_node_id
 * @property string $label
 * @property string $value
 * @property string $next_node_key
 * @property int|null $activates_tree_id
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\DecisionTree|null $activated_tree
 * @property-read \App\Models\DecisionNode $node
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DecisionOption newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DecisionOption newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DecisionOption query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DecisionOption whereActivatesTreeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DecisionOption whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DecisionOption whereDecisionNodeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DecisionOption whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DecisionOption whereLabel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DecisionOption whereNextNodeKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DecisionOption whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DecisionOption whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DecisionOption whereValue($value)
 * @mixin \Eloquent
 */
class DecisionOption extends Model
{
    /**
     * @var string[]
     */
    protected $fillable = [
        'decision_node_id', 'label', 'value', 'next_node_key', 'activates_tree_id', 'sort_order',
    ];

    /**
     * @return BelongsTo
     */
    public function node(): BelongsTo
    {
        return $this->belongsTo(DecisionNode::class, 'decision_node_id');
    }

    /**
     * @return BelongsTo
     */
    public function activated_tree(): BelongsTo
    {
        return $this->belongsTo(DecisionTree::class, 'activates_tree_id');
    }
}
