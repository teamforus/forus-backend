<?php


namespace App\Searches;

use App\Models\Fund;
use App\Models\Organization;
use App\Models\Product;
use App\Models\ProductReservation;
use App\Models\ReservationExtraPayment;
use App\Scopes\Builders\ReservationExtraPaymentQuery;
use Illuminate\Database\Eloquent\Builder;

class ReservationExtraPaymentsSearch extends BaseSearch
{
    /**
     * @param array $filters
     * @param Builder|null $builder
     */
    public function __construct(array $filters, Builder $builder = null)
    {
        parent::__construct($filters, $builder ?: ReservationExtraPayment::query());
    }

    /**
     * @return Builder|null
     */
    public function query(): ?Builder
    {
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
     * @param Builder|ReservationExtraPayment $builder
     * @return Builder|ReservationExtraPayment
     */
    protected function order(Builder|ReservationExtraPayment $builder): Builder|ReservationExtraPayment
    {
        $orderBy = $this->getFilter('order_by', 'paid_at');
        $orderDir = $this->getFilter('order_dir', 'desc');

        $builder = $this->appendSortableFields($builder, $orderBy);

        return ReservationExtraPayment::query()
            ->fromSub($this->appendSortableFields($builder, $orderBy), 'reservation_extra_payments')
            ->orderBy($orderBy, $orderDir)
            ->latest();
    }

    /**
     * @param Builder|ReservationExtraPayment $builder
     * @param string|null $orderBy
     * @return Builder|ReservationExtraPayment
     */
    public function appendSortableFields(
        Builder|ReservationExtraPayment $builder,
        ?string $orderBy,
    ): Builder|ReservationExtraPayment {
        $subQuery = match($orderBy) {
            'fund_name' => Fund::query()
                ->whereHas('vouchers.product_reservation', function(Builder $builder) {
                    $builder->whereColumn('product_reservations.id', 'product_reservation_id');
                })
                ->select('name')
                ->limit(1),
            'provider_name' => Organization::query()
                ->whereHas('products.product_reservations', function(Builder $builder) {
                    $builder->whereColumn('product_reservations.id', 'product_reservation_id');
                })
                ->select('name')
                ->limit(1),
            'product_name' => Product::query()
                ->whereHas('product_reservations', function(Builder $builder) {
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
}