<?php


namespace App\Searches;


use App\Models\Fund;
use App\Models\Voucher;
use App\Scopes\Builders\VoucherQuery;
use Illuminate\Database\Eloquent\Builder;

class VouchersSearch extends BaseSearch
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
     * @return Voucher|Builder
     */
    public function query(): ?Builder
    {
        /** @var Builder|Voucher $builder */
        $builder = parent::query();

        if ($this->getFilter('type') === Voucher::TYPE_BUDGET) {
            $builder->whereNull('product_id');
        }

        if ($this->getFilter('type') === Voucher::TYPE_PRODUCT) {
            $builder->whereNotNull('product_id');
        }

        if ($this->getFilter('allow_reimbursements')) {
            VoucherQuery::whereAllowReimbursements($builder);
        }

        if ($this->getFilter('implementation_id')) {
            $builder->whereRelation('fund.fund_config', [
                'implementation_id' => $this->getFilter('implementation_id')
            ]);
        }

        if ($this->getFilter('implementation_key')) {
            $builder->whereRelation('fund.fund_config.implementation', [
                'key' => $this->getFilter('implementation_key')
            ]);
        }

        if ($this->getFilter('state')) {
            $builder->where('state', $this->getFilter('state'));
        }

        if ($this->hasFilter('archived')) {
            $this->getFilter('archived') ?
                VoucherQuery::whereExpiredOrNotActive($builder) :
                VoucherQuery::whereNotExpiredAndActive($builder);
        }

        return $builder;
    }
}