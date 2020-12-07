<?php

namespace App\Models\Data;

use App\Models\Voucher;

/**
 * Class VoucherExportData
 * @property Voucher $voucher
 * @package App\Models\Data
 */
class VoucherExportData
{
    protected $data_only;
    protected $voucher;
    protected $name;

    /**
     * VoucherExportData constructor.
     * @param Voucher $voucher
     * @param bool|null $data_only
     */
    public function __construct(Voucher $voucher, ?bool $data_only = false)
    {
        $this->data_only = $data_only;
        $this->name = $data_only ? null : token_generator()->generate(6, 2);

        $this->voucher = $voucher;
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
        $assigned_to_identity = $this->voucher->identity_address && $this->voucher->is_granted;

        return array_merge($this->data_only ? [] : [
            'name' => $this->name,
        ], [
            'granted' => $assigned_to_identity ? 'Ja': 'Nee',
            'in_use' => $this->voucher->has_transactions ? 'Ja': 'Nee',
        ], $this->voucher->product ? [
            'product_name' => $this->voucher->product->name,
        ] : [], $assigned_to_identity ? [
            'reference_bsn' => $this->voucher->voucher_relation->bsn ?? null,
            'identity_bsn' => record_repo()->bsnByAddress($this->voucher->identity_address),
            'identity_email' => record_repo()->primaryEmailByAddress($this->voucher->identity_address),
        ] : [
            'reference_bsn' => null,
            'identity_bsn' => null,
            'identity_email' => null,
        ], [
            'note' => $this->voucher->note,
            'source' => $this->voucher->employee_id ? 'employee': 'user',
            'amount' => $this->voucher->amount,
            'fund_name' => $this->voucher->fund->name,
            'created_at' => format_date_locale($this->voucher->created_at),
            'expire_at' => format_date_locale($this->voucher->expire_at),
        ]);
    }
}