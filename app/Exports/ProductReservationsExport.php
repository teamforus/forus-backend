<?php

namespace App\Exports;

use App\Models\ProductReservation;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class ProductReservationsExport extends BaseFieldedExport
{
    protected Collection $data;
    protected array $fields;

    /**
     * @var array|\string[][]
     */
    protected static array $exportFields = [
        'code'          => 'Code',
        'product_name'  => 'Aanbod',
        'amount'        => 'Bedrag',
        'email'         => 'E-mailadres',
        'first_name'    => 'Naam',
        'last_name'     => 'Voornamen',
        'user_note'     => 'Opmerking',
        'phone'         => 'Telefoonnummer',
        'address'       => 'Adres',
        'birth_date'    => 'Geboortedatum',
        'state'         => 'Status',
        'created_at'    => 'Indien datum',
        'expire_at'     => 'Verlopen op',
    ];

    /**
     * @param EloquentCollection|array $reservations
     * @param array $fields
     */
    public function __construct(EloquentCollection|array $reservations, array $fields = [])
    {
        $this->data = $reservations;
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
                'user_note'     => $reservation->user_note ?: '-',
                'phone'         => $reservation->phone ?: '-',
                'address'       => $reservation->address ?: '-',
                'birth_date'    => format_date_locale($reservation->birth_date) ?: '-',
                'state'         => $reservation->state_locale,
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