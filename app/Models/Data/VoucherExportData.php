<?php

namespace App\Models\Data;

use App\Models\Voucher;

/**
 * @property Voucher $voucher
 */
class VoucherExportData
{
    protected ?bool $onlyData;
    protected Voucher $voucher;
    protected array $fields;
    protected string $name;

    /**
     * VoucherExportData constructor.
     * @param Voucher $voucher
     * @param array $fields
     * @param bool|null $onlyData
     */
    public function __construct(Voucher $voucher, array $fields, ?bool $onlyData = false)
    {
        $this->name = token_generator()->generate(6, 2);
        $this->fields = $fields;
        $this->voucher = $voucher;
        $this->onlyData = $onlyData;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @return Voucher
     * @noinspection PhpUnused
     */
    public function getVoucher(): Voucher
    {
        return $this->voucher;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $sponsor = $this->voucher->fund->organization;
        $assigned = $this->voucher->identity_address && $this->voucher->is_granted;
        $identity = $this->voucher->identity;
        $firstUseDate = $this->voucher->first_use_date;
        $allowRecords = $this->voucher->fund?->fund_config?->allow_voucher_records;

        $bsnData = $sponsor->bsn_enabled ? [
            'reference_bsn' => $this->voucher->voucher_relation->bsn ?? null,
            'identity_bsn' =>  $assigned ? $this->voucher->identity?->record_bsn?->value : null
        ]: [];

        $export_data = array_merge($this->onlyData ? [] : [
            'name' => $this->name,
        ], [
            'id' => $this->voucher->id,
            ...$bsnData,
            'identity_email' => $assigned ? ($identity?->email) : null,
            'activation_code' => $this->voucher->activation_code ?? null,
            'client_uid' => $this->voucher->client_uid ?? null,
            'source' => $this->voucher->source_locale,
            'amount' => $this->voucher->amount_total_cached,
            'amount_available' => $this->voucher->amount_available_cached,
            'note' => $this->voucher->note,
            'fund_name' => $this->voucher->fund->name,
            'implementation_name' => $this->voucher->fund->fund_config?->implementation?->name,
            'product_name' => $this->voucher->product?->name,
            'granted' => $assigned ? 'Ja': 'Nee',
            'created_at' => format_date_locale($this->voucher->created_at),
            'expire_at' => format_date_locale($this->voucher->expire_at),
            'in_use' => $this->voucher->in_use ? 'Ja': 'Nee',
            'in_use_date' => $firstUseDate ? format_date_locale($firstUseDate) : null,
            'state' => $this->voucher->state ?? null,
            'has_transactions' => $this->voucher->has_transactions ? 'Ja': 'Nee',
            'has_reservations' => $this->voucher->has_reservations ? 'Ja': 'Nee',
        ]);

        return array_only(array_merge(
            $export_data,
            $allowRecords ? $this->getRecordsData($this->voucher) : [],
        ), array_merge(['name'], $this->fields));
    }

    /**
     * @param Voucher $voucher
     * @return array
     */
    protected function getRecordsData(Voucher $voucher): array
    {
        return $voucher->voucher_records->reduce(function(array $data, $record) {
            return array_merge($data, [$record->record_type->key => $record->value]);
        }, []);
    }
}