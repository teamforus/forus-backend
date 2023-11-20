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
     * ProductReservationsSearch constructor.
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

        if ($this->hasFilter('state')) {
            $builder->where('state', $this->getFilter('state'));
        }

        if ($this->hasFilter('organization_id')) {
            $builder->whereHas('product_reservation.product', function (Builder $builder) {
                $builder->where('organization_id', $this->getFilter('organization_id'));
            });
        }

        if ($this->hasFilter('from')) {
            $builder->whereDate('created_at', '>=', $this->getFilter('from'));
        }

        if ($this->hasFilter('to')) {
            $builder->whereDate('created_at', '<=', $this->getFilter('to'));
        }

        if ($this->hasFilter('fund_id')) {
            $builder->whereHas('product_reservation.voucher', function (Builder $builder) {
                $builder->where('fund_id', $this->getFilter('fund_id'));
            });
        }

        return $this->order($builder);
    }

    /**
     * @param Builder $builder
     * @return Builder
     */
    protected function order(Builder $builder): Builder
    {
        $orderBy = $this->getFilter('order_by', 'paid_at');
        $orderDir = $this->getFilter('order_dir', 'desc');

        $builder = $this->appendSortableFields($builder, $orderBy);
        $builder = ReservationExtraPayment::query()->fromSub($builder, 'reservation_extra_payments');

        return $builder->orderBy($orderBy, $orderDir)->latest();
    }

    /**
     * @param Builder|ReservationExtraPayment $builder
     * @param string|null $orderBy
     * @return Builder|ReservationExtraPayment
     */
    public function appendSortableFields(
        Builder|ReservationExtraPayment $builder,
        ?string $orderBy
    ): Builder|ReservationExtraPayment {
        $subQuery = match($orderBy) {
            'fund_name' => Fund::query()
                ->whereHas('vouchers.product_reservation', function(Builder $builder) {
                    $builder->whereColumn('product_reservations.id', 'product_reservation_id'); // reservation_extra_payments.
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