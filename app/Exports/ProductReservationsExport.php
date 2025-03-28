<?php

namespace App\Exports;

use App\Models\ProductReservation;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class ProductReservationsExport extends BaseFieldedExport
{
    protected static string $transKey = 'reservations';

    /**
     * @var array|string[][]
     */
    protected static array $exportFields = [
        'code',
        'product_name',
        'amount',
        'email',
        'first_name',
        'last_name',
        'user_note',
        'phone',
        'address',
        'birth_date',
        'state',
        'created_at',
        'expire_at',
        'ean',
        'sku',
    ];

    /**
     * @param EloquentCollection|array $reservations
     * @param array $fields
     */
    public function __construct(EloquentCollection|array $reservations, array $fields = [])
    {
        $this->fields = $fields;
        $this->data = $reservations;
    }

    /**
     * @param Collection $data
     * @return Collection
     */
    public function export(Collection $data): Collection
    {
        return $this->exportTransform($data);
    }

    /**
     * @param Collection $data
     * @return Collection
     */
    protected function exportTransform(Collection $data): Collection
    {
        return $this->transformKeys($data->map(fn (ProductReservation $reservation) => array_only(
            $this->getRow($reservation), $this->fields
        )));
    }

    /**
     * @param ProductReservation $reservation
     * @return array
     */
    protected function getRow(ProductReservation $reservation): array
    {
        return [
            'code' => $reservation->code,
            'product_name' => $reservation->product->name,
            'amount' => currency_format($reservation->amount),
            'email' => $reservation->voucher->identity?->email,
            'first_name' => $reservation->first_name,
            'last_name' => $reservation->last_name,
            'user_note' => $reservation->user_note ?: '-',
            'phone' => $reservation->phone ?: '-',
            'address' => $reservation->address ?: '-',
            'birth_date' => format_date_locale($reservation->birth_date) ?: '-',
            'state' => $reservation->state_locale,
            'created_at' => format_date_locale($reservation->created_at),
            'expire_at' => format_date_locale($reservation->expire_at),
            'ean' => $reservation->product->ean,
            'sku' => $reservation->product->sku,
        ];
    }
}
