<?php


namespace App\Searches;

use App\Models\VoucherTransactionBulk;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class VoucherTransactionBulksSearch extends BaseSearch
{
    /**
     * @param array $filters
     * @param Builder $builder
     */
    public function __construct(array $filters, Builder $builder)
    {
        parent::__construct($filters, $builder);
    }

    /**
     * @return VoucherTransactionBulk|Builder
     */
    public function query(): ?Builder
    {
        /** @var Builder|VoucherTransactionBulk $builder */
        $builder = parent::query();

        if ($this->hasFilter('from') && $this->getFilter('from')) {
            $from = Carbon::createFromFormat('Y-m-d', $this->getFilter('from'));

            $builder->where(
                'created_at',
                '>=',
                $from->startOfDay()->format('Y-m-d H:i:s')
            );
        }

        if ($this->hasFilter('to') && $this->getFilter('to')) {
            $to = Carbon::createFromFormat('Y-m-d', $this->getFilter('to'));

            $builder->where(
                'created_at',
                '<=',
                $to->endOfDay()->format('Y-m-d H:i:s')
            );
        }

        if ($this->hasFilter('state') && $this->getFilter('state')) {
            $builder->where('state', $this->getFilter('state'));
        }

        if ($quantity_min = $this->getFilter('quantity_min')) {
            $builder->has('voucher_transactions', '>=', $quantity_min);
        }

        if ($quantity_max = $this->getFilter('quantity_max')) {
            $builder->has('voucher_transactions', '<=', $quantity_max);
        }

        if ($amount_min = $this->getFilter('amount_min')) {
            $builder->whereHas('voucher_transactions', function (Builder $builder) use ($amount_min) {
                $builder->selectRaw('SUM(`voucher_transactions`.`amount`) as `total_amount`');
                $builder->having('total_amount', '>=', $amount_min);
            });
        }

        if ($amount_max = $this->getFilter('amount_max')) {
            $builder->whereHas('voucher_transactions', function (Builder $builder) use ($amount_max) {
                $builder->selectRaw('SUM(`voucher_transactions`.`amount`) as `total_amount`');
                $builder->having('total_amount', '<=', $amount_max);
            });
        }

        return $builder;
    }
}