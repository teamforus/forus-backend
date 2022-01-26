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
    protected $fields_list;
    protected $name;

    /**
     * VoucherExportData constructor.
     * @param Voucher $voucher
     * @param array $fields_list
     * @param bool|null $data_only
     */
    public function __construct(Voucher $voucher, array $fields_list, ?bool $data_only = false)
    {
        $this->data_only = $data_only;
        $this->name = $data_only ? null : token_generator()->generate(6, 2);
        $this->fields_list = empty($fields_list) ? [] : $fields_list;

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

        $export_data = $this->data_only ? [] : [
            'name' => $this->name,
        ];

        foreach ($this->fields_list as $field_key) {
            $value = null;

            switch ($field_key) {
                case 'granted': $value = $assigned_to_identity ? 'Ja': 'Nee'; break;
                case 'in_use':  $value = $this->voucher->in_use ? 'Ja': 'Nee'; break;
                case 'in_use_date':  $value = format_date_locale($this->getFirstUsageDate()); break;
                case 'product_name':  $value = $this->voucher->product ? $this->voucher->product->name : null; break;
                case 'reference_bsn':  $value = $this->voucher->voucher_relation->bsn ?? null; break;
                case 'identity_bsn':  $value = $assigned_to_identity ? record_repo()->bsnByAddress($this->voucher->identity_address) : null; break;
                case 'identity_email':  $value = $assigned_to_identity && $identity ? $identity->primary_email->email : null; break;
                case 'state':  $value = $this->voucher->state ?? null; break;
                case 'activation_code':  $value = $this->voucher->activation_code ?? null; break;
                case 'activation_code_uid':  $value = $this->voucher->activation_code_uid ?? null; break;
                case 'note':  $value = $this->voucher->note ?? null; break;
                case 'source':  $value = $this->voucher->employee_id ? 'employee': 'user'; break;
                case 'amount':  $value = $this->voucher->amount; break;
                case 'fund_name':  $value = $this->voucher->fund->name; break;
                case 'created_at':  $value = format_date_locale($this->voucher->created_at); break;
                case 'expire_at':  $value = format_date_locale($this->voucher->expire_at); break;
            }

            $export_data[$field_key] = $value;
        }

        return $export_data;
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