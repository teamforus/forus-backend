<?php


namespace App\Searches;


use App\Models\Voucher;
use App\Scopes\Builders\VoucherQuery;
use Illuminate\Database\Eloquent\Builder;

class VouchersSearch extends BaseSearch
{
    /**
     * @param array $filters
     * @param Builder|null $builder
     */
    public function __construct(array $filters, Builder $builder = null)
    {
        parent::__construct($filters, $builder ?: Voucher::query());
    }

    /**
     * @return Builder|null
     */
    public function query(): ?Builder
    {
        $builder = parent::query();

        if ($this->getFilter('isMobileClient')) {
            $builder->whereHas('fund.fund_config', static function (Builder $builder) {
                $builder->where('has_external_vouchers', '=', false);
            });
        }

        if ($this->getFilter('type') === Voucher::TYPE_BUDGET) {
            $builder->whereNull('product_id');
        }

        if ($this->getFilter('type') === Voucher::TYPE_PRODUCT) {
            $builder->whereNotNull('product_id');
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