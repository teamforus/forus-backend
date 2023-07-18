<?php


namespace App\Searches;

use App\Models\BankConnectionAccount;
use App\Models\FundTopUp;
use App\Models\FundTopUpTransaction;
use Illuminate\Database\Eloquent\Builder;

class FundTopUpTransactionSearch extends BaseSearch
{
    /**
     * @param array $filters
     * @param Builder|null $builder
     */
    public function __construct(array $filters, Builder $builder = null)
    {
        parent::__construct($filters, $builder ?: FundTopUpTransaction::query());
    }

    /**
     * @return Builder|null
     */
    public function query(): ?Builder
    {
        $builder = parent::query();

        if ($q = $this->getFilter('q')) {
            $builder->where(function (Builder $query) use ($q) {
                $query->whereRelation('bank_connection_account', 'monetary_account_iban', 'LIKE', "%$q%");
                $query->orWhereRelation('fund_top_up', 'code', 'LIKE', "%$q%");
            });
        }

        if ($this->hasFilter('amount_min')) {
            $builder->where('amount', '>=', $this->getFilter('amount_min'));
        }

        if ($this->hasFilter('amount_max')) {
            $builder->where('amount', '<=', $this->getFilter('amount_max'));
        }

        if ($this->hasFilter('from')) {
            $builder->where('created_at','>=', $this->getFilterDate('from')->startOfDay());
        }

        if ($this->hasFilter('to')) {
            $builder->where('created_at','<=', $this->getFilterDate('to')->endOfDay());
        }

        return $this->order($builder);
    }

    /**
     * @param Builder $builder
     * @return Builder
     */
    protected function order(Builder $builder): Builder
    {
        $orderBy = $this->getFilter('order_by', 'created_at');
        $orderDir = $this->getFilter('order_dir', 'desc');

        $builder = $this->appendSortableFields($builder, $orderBy);
        $builder = FundTopUpTransaction::query()->fromSub($builder, 'fund_top_up_transactions');

        return $builder->orderBy($orderBy, $orderDir);
    }

    /**
     * @param Builder|FundTopUpTransaction $builder
     * @param string|null $orderBy
     * @return Builder|FundTopUpTransaction
     */
    public function appendSortableFields(
        Builder|FundTopUpTransaction $builder,
        ?string $orderBy
    ): Builder|FundTopUpTransaction {
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
}