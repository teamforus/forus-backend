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
    protected $voucher;
    protected $name;

    public function __construct(Voucher $voucher)
    {
        $this->name = token_generator()->generate(6, 2);
        $this->voucher = $voucher;
    }

    /**
     * @return string
     */
    public function getName(): string
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

    public function toArray(): array {
        return array_merge([
            'name' => $this->name
        ], $this->voucher->product ? [
            'product_name' => $this->voucher->product->name,
        ] : [], [
            'fund_name' => $this->voucher->fund->name,
            'created_at' => format_date_locale($this->voucher->created_at),
            'expire_at' => format_date_locale($this->voucher->expire_at),
        ]);
    }
}