<?php

namespace App\Models\Enums;

use App\Models\Voucher;

enum VoucherType: string
{
    case Regular = Voucher::TYPE_BUDGET;
    case Product = Voucher::TYPE_PRODUCT;

    /**
     * @param Voucher $voucher
     * @return self
     */
    public static function forVoucher(Voucher $voucher): self
    {
        return self::from($voucher->type);
    }
}
