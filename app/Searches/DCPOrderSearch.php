<?php

namespace App\Searches;

use App\Services\DCPService\Models\DCPOrder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class DCPOrderSearch extends BaseSearch
{
    public const string STATE_GROUP_PENDING = 'pending';
    public const string STATE_GROUP_ERROR = 'error';
    public const string STATE_GROUP_DUPLICATE = 'duplicate';
    public const string STATE_GROUP_IN_PROGRESS = 'in_progress';
    public const string STATE_GROUP_DONE = 'done';

    public const array STATE_GROUPS = [
        self::STATE_GROUP_PENDING,
        self::STATE_GROUP_ERROR,
        self::STATE_GROUP_DUPLICATE,
        self::STATE_GROUP_IN_PROGRESS,
        self::STATE_GROUP_DONE,
    ];

    /**
     * @param array $filters
     * @param Builder|Relation|DCPOrder $builder
     */
    public function __construct(array $filters, Builder|Relation|DCPOrder $builder)
    {
        parent::__construct($filters, $builder);
    }

    /**
     * @return Builder|Relation|DCPOrder
     */
    public function query(): Builder|Relation|DCPOrder
    {
        /** @var Builder|Relation|DCPOrder $builder */
        $builder = parent::query();

        if ($q = $this->getFilter('q')) {
            $builder->where(function (Builder $builder) use ($q) {
                $builder->where('order_id', 'LIKE', "%$q%");
                $builder->orWhere('order_id_dcp', 'LIKE', "%$q%");

                foreach (['fullname', 'address1', 'zipcode', 'city'] as $field) {
                    $builder->orWhereRaw(
                        "LOWER(JSON_UNQUOTE(JSON_EXTRACT(`payload`, '$.deliveryAddress.$field'))) LIKE ?",
                        ['%' . mb_strtolower($q) . '%'],
                    );
                }
            });
        }

        if ($state = $this->getFilter('state')) {
            $state === 'approved'
                ? $builder->where('state', DCPOrder::STATE_PENDING)->whereNotNull('dcp_order_bulk_id')
                : $builder->where('state', $state);
        }

        if ($stateGroup = $this->getFilter('state_group')) {
            self::whereStateGroup($builder, $stateGroup);
        }

        if ($bulkId = $this->getFilter('dcp_order_bulk_id')) {
            $builder->where('dcp_order_bulk_id', $bulkId);
        }

        if ($this->getFilter('awaiting_approval')) {
            DCPOrder::whereAwaitingApproval($builder);
        }

        return $builder;
    }

    /**
     * @param array $filters
     * @param Builder|Relation|DCPOrder $builder
     * @return array<string, int>
     */
    public static function stateGroupCounts(array $filters, Builder|Relation|DCPOrder $builder): array
    {
        $filters = array_diff_key($filters, array_flip(['state', 'state_group']));
        $count = fn (array $filters) => (new self($filters, $builder))->query()->count();

        return [
            'all' => $count($filters),
            ...array_combine(self::STATE_GROUPS, array_map(
                fn (string $group) => $count([...$filters, 'state_group' => $group]),
                self::STATE_GROUPS,
            )),
        ];
    }

    /**
     * @param Builder|Relation|DCPOrder $builder
     * @param string $stateGroup
     * @return Builder|Relation|DCPOrder
     */
    public static function whereStateGroup(
        Builder|Relation|DCPOrder $builder,
        string $stateGroup,
    ): Builder|Relation|DCPOrder {
        return match ($stateGroup) {
            self::STATE_GROUP_PENDING => DCPOrder::whereAwaitingApproval($builder),
            self::STATE_GROUP_ERROR => $builder->where('state', DCPOrder::STATE_ERROR),
            self::STATE_GROUP_DUPLICATE => $builder
                ->where('state', DCPOrder::STATE_SENT)
                ->whereNull('order_id_dcp'),
            self::STATE_GROUP_IN_PROGRESS => $builder->where(function (Builder $builder) {
                $builder->where(function (Builder $builder) {
                    $builder->where('state', DCPOrder::STATE_PENDING)->whereNotNull('dcp_order_bulk_id');
                })->orWhere(function (Builder $builder) {
                    $builder->where('state', DCPOrder::STATE_SENT)->whereNotNull('order_id_dcp');
                });
            }),
            self::STATE_GROUP_DONE => $builder->whereIn('state', [
                DCPOrder::STATE_CLOSED,
                DCPOrder::STATE_CANCELED,
            ]),
            default => $builder,
        };
    }
}
