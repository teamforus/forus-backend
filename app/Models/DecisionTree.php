<?php

namespace App\Models;

use App\Enums\NodeType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use RuntimeException;
use stdClass;

/**
 * @property int $id
 * @property int $implementation_id
 * @property int|null $organization_id
 * @property string $slug
 * @property string $name
 * @property bool $is_common
 * @property string $start_node_key
 * @property bool $is_active
 * @property string|null $application_url
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\DecisionNode[] $nodes
 * @property-read int|null $nodes_count
 * @property-read \App\Models\Organization|null $organization
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DecisionTree newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DecisionTree newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DecisionTree query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DecisionTree whereApplicationUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DecisionTree whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DecisionTree whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DecisionTree whereImplementationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DecisionTree whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DecisionTree whereIsCommon($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DecisionTree whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DecisionTree whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DecisionTree whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DecisionTree whereStartNodeKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DecisionTree whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class DecisionTree extends Model
{
    public const string ORGANIZATION_START = '@organization-start';

    /**
     * @var string[]
     */
    protected $fillable = [
        'slug', 'name', 'is_common', 'start_node_key', 'is_active', 'application_url',
        'implementation_id', 'organization_id',
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'is_common' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * @return HasMany
     */
    public function nodes(): HasMany
    {
        return $this->hasMany(DecisionNode::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @return BelongsTo
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @param Implementation|null $implementation
     * @return array
     */
    public static function build(?Implementation $implementation): array
    {
        if (!$implementation) {
            return [];
        }

        $trees = DecisionTree::query()
            ->where('implementation_id', $implementation->id)
            ->where('is_active', true)
            ->with(['nodes.options.activated_tree'])
            ->orderBy('id')
            ->get();

        $common = $trees->where('is_common', true)->first();

        if (!$common) {
            throw new RuntimeException('No active common decision tree is configured.');
        }

        return [
            'common' => static::serializeTree($common),
            'organizationTrees' => $trees
                ->reject(fn (DecisionTree $tree) => $tree->is_common)
                ->mapWithKeys(fn (DecisionTree $tree) => [$tree->slug => static::serializeTree($tree)])
                ->all() ?: new stdClass(),
        ];
    }

    /**
     * Checks the whole graph. Returns ['errors' => string[], 'warnings' => string[]].
     *
     * Errors:   broken data (missing start/next nodes, malformed nodes).
     * Warnings: nodes that can never be reached.
     */
    public static function validate(): array
    {
        $errors = [];
        $warnings = [];

        $treesGrouped = DecisionTree::with('nodes.options')
            ->orderBy('id')
            ->get()
            ->groupBy('implementation_id');

        foreach ($treesGrouped as $trees) {
            $commons = $trees->where('is_common', true);

            if ($commons->count() !== 1) {
                return ['errors' => ["Exactly one common tree is required, found {$commons->count()}."], 'warnings' => []];
            }

            $common = $commons->first();
            $byId = $trees->keyBy('id');
            $maps = $trees->mapWithKeys(fn (DecisionTree $t) => [$t->id => $t->nodes->keyBy('key')]);

            // 1. Structural checks per node.
            foreach ($trees as $tree) {
                if (!$maps[$tree->id]->has($tree->start_node_key)) {
                    $errors["start:$tree->id"] = "Tree '$tree->slug': start node '$tree->start_node_key' does not exist.";
                }

                foreach ($tree->nodes as $node) {
                    $where = "Tree '$tree->slug', node '$node->key'";

                    if (!$tree->is_common && $maps[$common->id]->has($node->key)) {
                        $warnings[] = "$where: overrides the node with the same key in the common tree.";
                    }

                    if ($node->type === NodeType::Result) {
                        if ($node->eligible === null) {
                            $errors[] = "$where: result nodes need an `eligible` value.";
                        }
                        if ($node->options->isNotEmpty()) {
                            $errors[] = "$where: result nodes must not have options.";
                        }
                        continue;
                    }

                    if ($node->options->isEmpty()) {
                        $errors[] = "$where: {$node->type->value} node has no options.";
                        continue;
                    }

                    if ($node->type === NodeType::Decision) {
                        $values = $node->options->pluck('value')->sort()->values()->all();
                        if ($values !== ['no', 'yes']) {
                            $errors[] = "$where: decision nodes need exactly the options 'yes' and 'no'.";
                        }
                    }
                }
            }

            // 2. Walk the graph from the common start node.
            $visited = [];
            $reached = [];
            $stack = [[$common->start_node_key, [], 'tree start']];

            while ($stack) {
                [$key, $active, $from] = array_pop($stack);

                // $active keeps activation order: the most recently activated tree supplies the organization start.
                $state = implode(',', $active) . '|' . $key;
                if (isset($visited[$state])) {
                    continue;
                }
                $visited[$state] = true;

                if ($key === self::ORGANIZATION_START) {
                    if (!$active) {
                        $errors["no-organization:$from"] = "Placeholder '" . self::ORGANIZATION_START . "' used by $from, but no organization tree is active on that path.";
                        continue;
                    }

                    $key = $byId[end($active)]->start_node_key;
                }

                $node = null;
                $ownerId = null;
                foreach ([$common->id, ...$active] as $treeId) {
                    if ($maps[$treeId]->has($key)) {
                        $node = $maps[$treeId][$key];
                        $ownerId = $treeId;
                    }
                }

                if (!$node) {
                    $context = $active
                        ? 'active trees: ' . implode(', ', array_map(fn ($id) => $byId[$id]->slug, $active))
                        : 'no organization tree active';
                    $errors["missing:$key:$context"] = "Node '$key' does not exist ($context), referenced from $from.";
                    continue;
                }

                $reached[$ownerId][$key] = true;

                // A result with a button needs a link: its own, or the active organization's application_url.
                if ($node->type === NodeType::Result && $node->show_button && !$node->button_url) {
                    $url = $active ? $byId[end($active)]->application_url : null;

                    if (!$url) {
                        $errors["button:$node->key:" . implode(',', $active)] =
                            "Result '$node->key' shows a button but has no button_url and the active organization" .
                            " tree has no application_url (referenced from $from).";
                    }
                }

                foreach ($node->options as $option) {
                    $nextActive = $active;
                    $activated = $option->activates_tree_id;

                    if ($activated && $byId->has($activated) && !in_array($activated, $nextActive, true)) {
                        $nextActive[] = $activated;
                    }

                    $stack[] = [$option->next_node_key, $nextActive, "option '$option->value' of '$node->key'"];
                }
            }

            // 3. Unreachable nodes.
            foreach ($trees as $tree) {
                foreach ($tree->nodes as $node) {
                    if (!isset($reached[$tree->id][$node->key])) {
                        $warnings[] = "Tree '$tree->slug': node '$node->key' is never reached.";
                    }
                }
            }
        }

        return ['errors' => array_values($errors), 'warnings' => $warnings];
    }

    /**
     * @param DecisionTree $tree
     * @return array
     */
    private static function serializeTree(DecisionTree $tree): array
    {
        $nodes = [];

        foreach ($tree->nodes as $node) {
            $nodes[$node->key] = static::serializeNode($node);
        }

        return [
            'start' => $tree->start_node_key,
            'nodes' => $nodes ?: new stdClass(),
        ] + ($tree->application_url ? ['applicationUrl' => $tree->application_url] : []);
    }

    /**
     * @param DecisionNode $node
     * @return array|bool[]
     */
    private static function serializeNode(DecisionNode $node): array
    {
        $base = [
            'type' => $node->type->value,
            'id' => $node->key,
            'label' => $node->label,
        ];

        if ($node->description) {
            $base['description'] = $node->description;
        }

        if ($node->type === NodeType::Result) {
            return $base + [
                'eligible' => (bool) $node->eligible,
                'button' => $node->show_button,
            ];
        }

        return $base + [
            'options' => $node->options->map(function (DecisionOption $option) {
                $data = [
                    'label' => $option->label,
                    'value' => $option->value,
                    'next' => $option->next_node_key,
                ];

                if ($option->activated_tree) {
                    if ($option->activated_tree->is_active) {
                        $data = [
                            ...$data,
                            'label' => $option->activated_tree->organization?->name ?? $option->label,
                            'tree' => $option->activated_tree->slug,
                        ];
                    } else {
                        return null;
                    }
                }

                return $data;
            })->filter()->values()->all(),
        ];
    }
}
