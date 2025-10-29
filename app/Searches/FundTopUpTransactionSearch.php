<?php

namespace App\Searches;

use App\Models\BankConnectionAccount;
use App\Models\FundTopUp;
use App\Models\FundTopUpTransaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class FundTopUpTransactionSearch extends BaseSearch
{
    /**
     * @param array $filters
     * @param Builder|Relation|FundTopUpTransaction $builder
     */
    public function __construct(array $filters, Builder|Relation|FundTopUpTransaction $builder)
    {
        parent::__construct($filters, $builder);
    }

    /**
     * @return Builder|Relation|FundTopUpTransaction
     */
    public function query(): Builder|Relation|FundTopUpTransaction
    {
        /** @var Builder|Relation|FundTopUpTransaction $builder */
        $builder = parent::query();

        if ($q = $this->getFilter('q')) {
            $builder->where(function (Builder $query) use ($q) {
                $query->whereRelation('bank_connection_account', 'monetary_account_iban', 'LIKE', "%$q%");
                $query->orWhereRelation('fund_top_up', 'code', 'LIKE', "%$q%");
            });
        }

        if ($this->getFilter('amount_min')) {
            $builder->where('amount', '>=', $this->getFilter('amount_min'));
        }

        if ($this->getFilter('amount_max')) {
            $builder->where('amount', '<=', $this->getFilter('amount_max'));
        }

        if ($this->hasFilter('from')) {
            $builder->where('created_at', '>=', $this->getFilterDate('from')->startOfDay());
        }

        if ($this->hasFilter('to')) {
            $builder->where('created_at', '<=', $this->getFilterDate('to')->endOfDay());
        }

        return $this->order($builder);
    }

    /**
     * @param Builder|Relation|FundTopUpTransaction $builder
     * @param string|null $orderBy
     * @return Builder|Relation|FundTopUpTransaction
     */
    public function appendSortableFields(
        Builder|Relation|FundTopUpTransaction $builder,
        ?string $orderBy
    ): Builder|Relation|FundTopUpTransaction {
        $subQuery = match($orderBy) {
            'code' => FundTopUp::query()
                ->whereColumn('id', 'fund_top_up_transactions.fund_top_up_id')
                ->select('code')
                ->limit(1),
            'iban' => BankConnectionAccount::query()
                ->whereColumn('id', 'fund_top_up_transactions.bank_connection_account_id')
                ->select('monetary_account_iban')
                ->limit(1),
            default => null,
        };

        return $builder->addSelect($subQuery ? [
            $orderBy => $subQuery,
        ] : []);
    }

    /**
     * @param Builder|Relation|FundTopUpTransaction $builder
     * @return Builder|Relation|FundTopUpTransaction
     */
    protected function order(Builder|Relation|FundTopUpTransaction $builder): Builder|Relation|FundTopUpTransaction
    {
        $orderBy = $this->getFilter('order_by', 'created_at');
        $orderDir = $this->getFilter('order_dir', 'desc');

        $builder = $this->appendSortableFields($builder, $orderBy);
        $builder = FundTopUpTransaction::query()->fromSub($builder, 'fund_top_up_transactions');

        return $builder->orderBy($orderBy, $orderDir);
    }
}
