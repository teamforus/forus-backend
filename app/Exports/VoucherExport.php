<?php

namespace App\Exports;

use Illuminate\Support\Collection;

class VoucherExport extends BaseFieldedExport
{
    protected Collection $data;

    /**
     * @var array|\string[][]
     */
    protected static array $exportFields = [
        'id' => 'ID',
        'granted' => 'Toegekend',
        'identity_email' => 'E-mailadres',
        'in_use' => 'In gebruik',
        'has_transactions' => 'Has transactions',
        'has_reservations' => 'Has reservations',
        'state' => 'Status',
        'amount' => 'Bedrag',
        'amount_available' => 'Huidig bedrag',
        'in_use_date' => 'In gebruik datum',
        'activation_code' => 'Activatiecode',
        'fund_name' => 'Fondsnaam',
        'reference_bsn' => 'BSN (door medewerker)',
        'client_uid' => 'Uniek nummer',
        'created_at' => 'Aangemaakt op',
        'identity_bsn' => 'BSN (DigiD)',
        'source' => 'Aangemaakt door',
        'product_name' => 'Aanbod naam',
        'note' => 'Notitie',
        'expire_at' => 'Verlopen op',
    ];

    /**
     * @var array|string[]
     */
    protected static array $productVoucherOnlyKeys = [
        'product_name',
    ];

    /**
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->data = collect($data);
    }

    /**
     * @param string $type
     * @return array
     */
    public static function getExportFields(string $type = 'budget') : array
    {
        $fields = array_merge(parent::getExportFields(), self::addRecordFields());

        if ($type === 'product') {
            return $fields;
        }

        return array_values(array_filter($fields, static function(array $item) {
            return !in_array($item['key'], static::$productVoucherOnlyKeys);
        }));
    }

    /**
     * @return array
     */
    private static function addRecordFields() : array
    {
        $types = collect(record_types_cached())->filter(fn($record) => $record['vouchers']);

        return array_map(fn ($type) => [
            'name'  => $type['name'],
            'key'   => $type['key'],
            'is_record_field' => true,
        ], $types->toArray());
    }
}