<?php

namespace App\Exports;

use App\Exports\Base\BaseArrayExport;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class VoucherExport extends BaseArrayExport
{
    protected static string $transKey = 'vouchers';

    /**
     * @var array|\string[][]
     */
    protected static array $exportFields = [
        'number',
        'reference_bsn',
        'identity_bsn',
        'identity_email',
        'activation_code',
        'client_uid',
        'source',
        'amount',
        'amount_available',
        'note',
        'fund_name',
        'implementation_name',
        'product_name',
        'granted',
        'created_at',
        'expire_at',
        'in_use',
        'in_use_date',
        'state',
        'has_transactions',
        'has_reservations',
        'has_payouts',
    ];

    /**
     * @var array|string[]
     */
    protected static array $productVoucherOnlyKeys = [
        'product_name',
    ];

    /**
     * @param string $type
     * @return array
     */
    public static function getExportFields(string $type = 'budget'): array
    {
        $fields = array_merge(parent::getExportFields(), self::addRecordFields());

        if ($type === 'product') {
            return $fields;
        }

        return array_values(array_filter($fields, static function (array $item) {
            return !in_array($item['key'], static::$productVoucherOnlyKeys);
        }));
    }

    /**
     * @param string $type
     * @return array
     */
    public static function getExportFieldsRaw(string $type = 'budget'): array
    {
        $fields = parent::getExportFieldsRaw();

        if ($type === 'product') {
            return $fields;
        }

        return array_values(array_filter($fields, static function (string $item) {
            return !in_array($item, static::$productVoucherOnlyKeys);
        }));
    }

    /**
     * @param array $rows
     * @return Collection
     */
    protected function collectArrayRowValues(array $rows): Collection
    {
        return collect($rows)
            ->map(fn (array $row) => Arr::only($row, $this->getBaseFieldKeys()))
            ->values();
    }

    protected function getFieldLabels(): array
    {
        return Arr::pluck(static::getExportFields('product'), 'name', 'key');
    }

    /**
     * @param Collection $rowValues
     * @param array $columns
     * @return array
     */
    protected function filterColumnDefinitions(Collection $rowValues, array $columns): array
    {
        if ($rowValues->isEmpty()) {
            return $columns;
        }

        return array_values(array_filter($columns, fn (array $column) => $rowValues->contains(
            fn (array $rowValues) => array_key_exists($column['key'], $rowValues),
        )));
    }

    /**
     * @return array
     */
    private static function addRecordFields(): array
    {
        return array_map(fn ($type) => [
            'key' => $type['key'],
            'name' => $type['name'],
            'is_record_field' => true,
        ], array_filter(record_types_cached(), fn ($record) => $record['vouchers'] ?? false));
    }
}
