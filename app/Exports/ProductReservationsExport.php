<?php

namespace App\Exports;

use App\Exports\Base\BaseExport;
use App\Models\ProductReservation;
use Illuminate\Database\Eloquent\Model;

class ProductReservationsExport extends BaseExport
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
        'transaction_id',
    ];

    /**
     * @param Model|ProductReservation $model
     * @return array
     */
    protected function getRow(Model|ProductReservation $model): array
    {
        return [
            'code' => $model->code,
            'product_name' => $model->product->name,
            'amount' => currency_format($model->amount),
            'email' => $model->voucher->identity?->email,
            'first_name' => $model->first_name,
            'last_name' => $model->last_name,
            'user_note' => $model->user_note ?: '-',
            'phone' => $model->phone ?: '-',
            'address' => $model->address ?: '-',
            'birth_date' => format_date_locale($model->birth_date) ?: '-',
            'state' => $model->state_locale,
            'created_at' => format_date_locale($model->created_at),
            'expire_at' => format_date_locale($model->expire_at),
            'ean' => $model->product->ean,
            'sku' => $model->product->sku,
            'transaction_id' => $model->voucher_transaction?->id,
        ];
    }
}
