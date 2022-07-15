<?php

namespace App\Models\Data;

use App\Models\Voucher;
use Illuminate\Support\Carbon;

/**
 * Class VoucherExportData
 * @property Voucher $voucher
 * @package App\Models\Data
 */
class VoucherExportData
{
    protected $onlyData;
    protected $voucher;
    protected $fields;
    protected $name;

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

        $bsnData = $sponsor->bsn_enabled ? [
            'reference_bsn' => $this->voucher->voucher_relation->bsn ?? null,
            'identity_bsn' =>  $assigned ? $this->voucher->identity?->bsn : null
        ]: [];

        $export_data = array_merge($this->onlyData ? [] : [
            'name' => $this->name,
        ], [
            'granted' => $assigned ? 'Ja': 'Nee',
            'in_use' => $this->voucher->in_use ? 'Ja': 'Nee',
            'in_use_date' => format_date_locale($this->getFirstUsageDate()),
            'product_name' => $this->voucher->product?->name,
        ], $bsnData, [
            'identity_email' => $assigned ? ($identity?->email) : null,
            'state' => $this->voucher->state ?? null,
            'activation_code' => $this->voucher->activation_code ?? null,
            'activation_code_uid' => $this->voucher->activation_code_uid ?? null,
            'note' => $this->voucher->note,
            'source' => $this->voucher->employee_id ? 'employee': 'user',
            'amount' => $this->voucher->amount,
            'fund_name' => $this->voucher->fund->name,
            'created_at' => format_date_locale($this->voucher->created_at),
            'expire_at' => format_date_locale($this->voucher->expire_at),
        ]);

        return array_only($export_data, array_merge(['name'], $this->fields));
    }

    /**
     * @return Carbon|null
     */
    public function getFirstUsageDate(): ?Carbon
    {
        $voucher = $this->voucher;
        $productVouchers = $voucher->product_vouchers->whereNull('product_reservation_id');
        $reservationVouchers = $voucher->product_vouchers->whereNotNull('product_reservation_id');
        $reservationTransactions = $reservationVouchers->pluck('transactions')->flatten();

        $models = $voucher->transactions->merge($reservationTransactions)->merge($productVouchers);

        if ($models->count() > 0) {
            return $models->sortBy('created_at')[0]->created_at;
        }

        return null;
    }
}