<?php

namespace App\Exports;

use Illuminate\Support\Collection;

class VoucherExport extends BaseFieldedExport
{
    protected Collection $data;
    protected array $fields;

    /**
     * @var array|\string[][]
     */
    protected static array $exportFields = [
        'number' => 'Nummer',
        'granted' => 'Toegekend',
        'identity_email' => 'E-mailadres',
        'in_use' => 'In gebruik',
        'has_payouts' => 'Heeft uitbetalingen',
        'has_transactions' => 'Transactie gemaakt',
        'has_reservations' => 'Reservering gemaakt',
        'state' => 'Status',
        'amount' => 'Bedrag',
        'amount_available' => 'Huidig bedrag',
        'in_use_date' => 'In gebruik datum',
        'activation_code' => 'Activatiecode',
        'fund_name' => 'Fondsnaam',
        'implementation_name' => 'Website',
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
     * @param array $fields
     */
    public function __construct(array $data, array $fields)
    {
        $this->data = collect($data);
        $this->fields = $fields;
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
        return array_map(fn ($type) => [
            'key' => $type['key'],
            'name' => $type['name'],
            'is_record_field' => true,
        ], array_filter(record_types_cached(), fn($record) => $record['vouchers'] ?? false));
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        $collection = $this->collection();

        return array_map(
            fn ($key) => static::$exportFields[$key] ?? $key,
            $collection->isNotEmpty() ? array_keys($collection->first()) : $this->fields
        );
    }
}
