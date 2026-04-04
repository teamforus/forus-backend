<?php

namespace App\Exports;

use App\Exports\Base\BaseExport;
use App\Models\ProductReservation;
use App\Models\ProductReservationFieldValue;
use App\Models\ReservationField;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;

class ProductReservationsExport extends BaseExport
{
    protected Collection $fieldList;
    protected static string $transKey = 'reservations';

    protected const string DYNAMIC_FIELD_RECORDS = 'records';
    protected const array DYNAMIC_FIELDS_KEYS = [self::DYNAMIC_FIELD_RECORDS];

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
        'product',
        'voucher.identity.primary_email',
        'voucher_transaction',
        'custom_fields.files',
        'custom_fields.reservation_field',
    ];

    /**
     * @param Builder|Relation|ProductReservation $builder
     * @param array $fields
     */
    public function __construct(Builder|Relation|ProductReservation $builder, array $fields)
    {
        $fieldIds = in_array(static::DYNAMIC_FIELD_RECORDS, $fields, true)
            ? ProductReservationFieldValue::query()
                ->whereIn('product_reservation_id', (clone $builder)->select('id'))
                ->pluck('reservation_field_id')
                ->toArray()
            : [];

        $this->fieldList = ReservationField::withTrashed()
            ->whereIn('id', $fieldIds)
            ->orderBy('order')
            ->orderBy('id')
            ->pluck('label', 'id');

        parent::__construct($builder, $fields);
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
        return array_map(function (ProductReservationFieldValue|null $record) {
            if ($record?->reservation_field?->isTypeFile()) {
                return $record->files->pluck('original_name')->join(', ') ?: ($record->value ?: '-');
            }

            return $record?->value ?: '-';
        }, $records);
    }

    protected function getDynamicColumnDefinitionsFor(string $fieldKey): array
    {
        if ($fieldKey !== static::DYNAMIC_FIELD_RECORDS || !$this->shouldExpandDynamicField($fieldKey)) {
            return [];
        }

        return $this->fieldList->map(fn (string $label, int $id) => [
            'key' => static::makeDynamicColumnKey($id, 'reservation_field'),
            'label' => $label,
        ])->values()->all();
    }

    /**
     * @param string $fieldKey
     * @param Model|ProductReservation $model
     * @return array
     */
    protected function getDynamicRowValuesFor(string $fieldKey, Model|ProductReservation $model): array
    {
        if ($fieldKey !== static::DYNAMIC_FIELD_RECORDS || !$this->shouldExpandDynamicField($fieldKey)) {
            return [];
        }

        $records = $this->fieldList->mapWithKeys(fn (string $label, int $id) => [
            $id => $model->custom_fields->firstWhere('reservation_field_id', $id),
        ])->all();

        $records = static::getRecords($records);

        return $this->fieldList->mapWithKeys(fn (string $label, int $id) => [
            static::makeDynamicColumnKey($id, 'reservation_field') => $records[$id] ?? null,
        ])->all();
    }
}
