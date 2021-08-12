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
        $identity = $this->voucher->identity;

        return array_merge($this->data_only ? [] : [
            'name' => $this->name,
        ], [
            'granted' => $assigned_to_identity ? 'Ja': 'Nee',
            'in_use' => $this->voucher->in_use ? 'Ja': 'Nee',
            'in_use_date' => format_date_locale($this->getFirstUsageDate()),
        ], $this->voucher->product ? [
            'product_name' => $this->voucher->product->name,
        ] : [], $assigned_to_identity ? [
            'reference_bsn' => $this->voucher->voucher_relation->bsn ?? null,
            'identity_bsn' => record_repo()->bsnByAddress($this->voucher->identity_address),
            'identity_email' => $identity ? $identity->primary_email->email : null,
        ] : [
            'reference_bsn' => $this->voucher->voucher_relation->bsn ?? null,
            'identity_bsn' => null,
            'identity_email' => null,
        ], [
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