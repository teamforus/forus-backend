<?php

namespace App\Exports;

use Illuminate\Support\Collection;

class VoucherExport extends BaseFieldedExport
{
    protected static string $transKey = 'vouchers';

    /**
     * @var array|\string[][]
     */
    protected static array $exportFields = [
        'number',
        'granted',
        'identity_email',
        'in_use',
        'has_payouts',
        'has_transactions',
        'has_reservations',
        'state',
        'amount',
        'amount_available',
        'in_use_date',
        'activation_code',
        'fund_name',
        'implementation_name',
        'reference_bsn',
        'client_uid',
        'created_at',
        'identity_bsn',
        'source',
        'product_name',
        'note',
        'expire_at',
    ];

    /**
     * @var array|string[]
     */
    protected static array $productVoucherOnlyKeys = [
        'product_name',
    ];

    /**
     * @param array $data
     * @param array $fields
     */
    public function __construct(array $data, protected array $fields)
    {
        $this->data = $this->exportTransform(collect($data));
    }

    /**
     * @param Collection $data
     * @return Collection
     */
    protected function exportTransform(Collection $data): Collection
    {
        return $this->transformKeys($data);
    }

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
