<?php

namespace App\Searches;

use App\Models\Fund;
use App\Models\Organization;
use App\Models\Product;
use App\Models\ProductReservation;
use App\Models\ReservationExtraPayment;
use App\Scopes\Builders\ReservationExtraPaymentQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class ReservationExtraPaymentsSearch extends BaseSearch
{
    /**
     * @param array $filters
     * @param Builder|Relation|ReservationExtraPayment $builder
     */
    public function __construct(array $filters, Builder|Relation|ReservationExtraPayment $builder)
    {
        parent::__construct($filters, $builder);
    }

    /**
     * @return Builder|Relation|ReservationExtraPayment
     */
    public function query(): Builder|Relation|ReservationExtraPayment
    {
        /** @var Builder|Relation|ReservationExtraPayment $builder */
        $builder = parent::query();

        if ($this->hasFilter('q') && $this->getFilter('q')) {
            $builder = ReservationExtraPaymentQuery::whereQueryFilter($builder, $this->getFilter('q'));
        }

        if ($this->hasFilter('fund_id')) {
            $builder->whereHas('product_reservation.voucher', function (Builder $builder) {
                $builder->where('fund_id', $this->getFilter('fund_id'));
            });
        }

        return $this->order($builder);
    }

    /**
     * @param Builder|Relation|ReservationExtraPayment $builder
     * @param string|null $orderBy
     * @return Builder|Relation|ReservationExtraPayment
     */
    public function appendSortableFields(
        Builder|Relation|ReservationExtraPayment $builder,
        ?string $orderBy,
    ): Builder|Relation|ReservationExtraPayment {
        $subQuery = match($orderBy) {
            'fund_name' => Fund::query()
                ->whereHas('vouchers.product_reservation', function (Builder $builder) {
                    $builder->whereColumn(
                        'product_reservations.id',
                        'reservation_extra_payments.product_reservation_id'
                    );
                })
                ->select('name')
                ->limit(1),
            'provider_name' => Organization::query()
                ->whereHas('products.product_reservations', function (Builder $builder) {
                    $builder->whereColumn('product_reservations.id', 'product_reservation_id');
                })
                ->select('name')
                ->limit(1),
            'product_name' => Product::query()
                ->whereHas('product_reservations', function (Builder $builder) {
                    $builder->whereColumn('product_reservations.id', 'product_reservation_id');
                })
                ->select('name')
                ->limit(1),
            'price' => ProductReservation::query()
                ->whereColumn('product_reservations.id', 'product_reservation_id')
                ->select('price')
                ->limit(1),
            default => null,
        };

        return $builder->addSelect($subQuery ? [
            $orderBy => $subQuery,
        ] : []);
    }

    /**
     * @param Builder|Relation|ReservationExtraPayment $builder
     * @return Builder|Relation|ReservationExtraPayment
     */
    protected function order(Builder|Relation|ReservationExtraPayment $builder): Builder|Relation|ReservationExtraPayment
    {
        $orderBy = $this->getFilter('order_by', 'paid_at');
        $orderDir = $this->getFilter('order_dir', 'desc');

        return ReservationExtraPayment::query()
            ->fromSub($this->appendSortableFields($builder, $orderBy), 'reservation_extra_payments')
            ->orderBy($orderBy, $orderDir)
            ->latest();
    }
}
