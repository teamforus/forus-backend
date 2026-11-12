<?php

namespace App\Modules\DCP\Searches;

use App\Modules\DCP\Enums\DCPOrderState;
use App\Modules\DCP\Enums\DCPOrderStateGroup;
use App\Modules\DCP\Models\DCPOrder;
use App\Modules\VoucherPass\Models\VoucherPass;
use App\Searches\BaseSearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;

class DCPOrderSearch extends BaseSearch
{
    /**
     * @return Builder|Relation|DCPOrder
     */
    public function query(): Builder|Relation|DCPOrder
    {
        /** @var Builder|Relation|DCPOrder $query */
        $query = parent::query();
        $q = $this->getFilter('q');

        if (filled($q)) {
            $query->where(function (Builder $query) use ($q) {
                foreach ([
                    'id', 'order_id', 'order_id_dcp', 'delivery_first_name', 'delivery_last_name',
                    'delivery_address1', 'delivery_postal_code', 'delivery_city',
                ] as $column) {
                    $query->orWhere($column, 'LIKE', '%' . $q . '%');
                }

                foreach (['printedCardNumber', 'printedCardType'] as $field) {
                    $query->orWhereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(order_rows, ?))) LIKE LOWER(?)', [
                        '$[0].tagIds[0].' . $field, '%' . $q . '%',
                    ]);
                }

                $query->orWhereHasMorph('orderable', VoucherPass::class, function (Builder $query) use ($q) {
                    $query->whereColumn('voucher_passes.organization_id', 'dcp_orders.organization_id');
                    $query->where('external_id', 'LIKE', '%' . $q . '%');
                });
            });
        }

        if ($state = $this->getFilter('state')) {
            $query->where('state', $state);
        }

        if ($group = $this->getFilter('state_group')) {
            $query->whereIn('state', DCPOrderStateGroup::from($group)->states());
        }

        if ($bulkId = $this->getFilter('dcp_order_bulk_id')) {
            $query->where('dcp_order_bulk_id', $bulkId);
        }

        return $query;
    }

    /**
     * @param array $filters
     * @param Builder $query
     * @return array<string, int>
     */
    public static function stateGroupCounts(array $filters, Builder $query): array
    {
        $search = new self(Arr::except($filters, ['state', 'state_group']), $query);
        $counts = ['all' => 0, ...array_fill_keys(array_column(DCPOrderStateGroup::cases(), 'value'), 0)];

        $states = $search->query()->select('state')->selectRaw('COUNT(*) as aggregate')->groupBy('state')->get();

        foreach ($states as $state) {
            $count = (int) $state->getAttribute('aggregate');
            /** @var DCPOrderState $orderState */
            $orderState = $state->getAttribute('state');

            $counts['all'] += $count;
            $counts[DCPOrderStateGroup::forState($orderState)->value] += $count;
        }

        return $counts;
    }
}
