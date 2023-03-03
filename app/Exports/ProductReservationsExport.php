<?php

namespace App\Exports;

use App\Models\ProductReservation;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

/**
 * Class VoucherExport
 * @package App\Exports
 */
class ProductReservationsExport extends BaseFieldedExport
{
    protected Collection $data;
    protected array $fields;

    /**
     * @var array|\string[][]
     */
    protected static array $exportFields = [
        'code'          => 'Code',
        'product_name'  => 'Product name',
        'amount'        => 'Bedrag',
        'email'         => 'E-mail',
        'first_name'    => 'First name',
        'last_name'     => 'Last name',
        'user_note'     => 'User note',
        'state'         => 'Status',
        'created_at'    => 'Created at',
        'expire_at'     => 'Verlopen op',
    ];

    /**
     * @param EloquentCollection|array $reservations
     * @param array $fields
     */
    public function __construct(EloquentCollection|array $reservations, array $fields = [])
    {
        $this->data   = $reservations;
        $this->fields = $fields;
    }

    /**
     * @return array
     */
    public static function getExportFields() : array
    {
        return array_reduce(array_keys(static::$exportFields), fn($list, $key) => array_merge($list, [[
            'key' => $key,
            'name' => static::$exportFields[$key],
        ]]), []);
    }

    /**
     * @return Collection
     */
    public function collection(): Collection
    {
        $data = $this->data->map(function(ProductReservation $reservation) {
            return array_only([
                'code'          => $reservation->code,
                'product_name'  => $reservation->product->name,
                'amount'        => currency_format($reservation->amount),
                'email'         => $reservation->voucher->identity?->email,
                'first_name'    => $reservation->first_name,
                'last_name'     => $reservation->last_name,
                'user_note'     => $reservation->user_note,
                'state'         => $reservation->state,
                'created_at'    => format_date_locale($reservation->created_at),
                'expire_at'     => format_date_locale($reservation->expire_at),
            ], $this->fields);
        });

        return $data->map(function($item) {
            return array_reduce(array_keys($item), fn($obj, $key) => array_merge($obj, [
                static::$exportFields[$key] => (string) $item[$key],
            ]), []);
        });
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return array_keys($this->collection()->first());
    }
}