<?php

namespace App\Exports;

use App\Exports\Base\BaseFieldedExport;
use Illuminate\Support\Collection;

class VoucherExport extends BaseFieldedExport
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
     * @param array $data
     * @param array $fields
     */
    public function __construct(array $data, protected array $fields)
    {
        $this->data = $this->exportTransform(collect($data));
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
     * @param Collection $data
     * @return Collection
     */
    protected function exportTransform(Collection $data): Collection
    {
        return $this->transformKeys($data);
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
