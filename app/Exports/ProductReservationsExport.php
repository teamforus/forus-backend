<?php

namespace App\Exports;

use App\Exports\Base\BaseExport;
use App\Models\ProductReservation;
use App\Models\ProductReservationFieldValue;
use App\Models\ReservationField;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class ProductReservationsExport extends BaseExport
{
    protected static string $transKey = 'reservations';
    protected Collection $fieldList;

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
        'records',
    ];

    /**
     * @var array|string[]
     */
    protected array $builderWithArray = [
        'custom_fields.files',
    ];

    /**
     * @param Builder|Relation|ProductReservation $builder
     * @param array $fields
     */
    public function __construct(Builder|Relation|ProductReservation $builder, protected array $fields)
    {
        $fieldIds = ProductReservationFieldValue::query()
            ->whereIn('product_reservation_id', (clone $builder)->select('id'))
            ->pluck('reservation_field_id')
            ->toArray();

        $this->fieldList = ReservationField::query()->whereIn('id', $fieldIds)->pluck('label', 'id');

        parent::__construct($builder, $fields);
    }

    /**
     * @param Collection $data
     * @return Collection
     */
    protected function exportTransform(Collection $data): Collection
    {
        $fieldLabels = Arr::pluck(static::getExportFields(), 'name', 'key');

        return $data->map(function (ProductReservation $reservation) use ($fieldLabels) {
            $row = Arr::only($this->getRow($reservation), $this->fields);

            $row = array_reduce(array_keys($row), fn ($obj, $key) => array_merge($obj, [
                $fieldLabels[$key] => $row[$key],
            ]), []);

            $records = (array) $this->fieldList->reduce(fn ($records, $value, $key) => [
                ...$records, $value => $reservation->custom_fields->firstWhere('reservation_field_id', $key),
            ], []);

            $records = in_array('records', $this->fields) ? static::getRecords($records) : [];

            return [...$row, ...$records];
        })->values();
    }

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

    /**
     * @param Collection|array $records
     * @return array|string[]
     */
    protected static function getRecords(Collection|array $records): array
    {
        return array_map(function (?ProductReservationFieldValue $record = null) {
            $file = $record?->files[0] ?? null;

            return $file ? $file->original_name : ($record?->value ?: '-');
        }, $records);
    }
}
