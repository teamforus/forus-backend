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
}