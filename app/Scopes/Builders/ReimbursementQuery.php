<?php


namespace App\Scopes\Builders;

use App\Models\Fund;
use App\Models\ProductReservation;
use App\Models\Reimbursement;
use App\Models\Voucher;
use App\Models\Identity;
use App\Models\IdentityEmail;
use App\Models\VoucherTransaction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QBuilder;
use Illuminate\Support\Facades\DB;

class ReimbursementQuery
{
    /**
     * @param Builder|Reimbursement|Relation $builder
     * @return Builder|Reimbursement|Relation
     */
    public static function whereExpired(
        Builder|Reimbursement|Relation $builder
    ): Builder|Reimbursement|Relation {
        return $builder->whereHas('voucher', fn(Builder $q) => VoucherQuery::whereExpired($q));
    }

    /**
     * @param Builder|Reimbursement|Relation $builder
     * @return Builder|Reimbursement|Relation
     */
    public static function whereNotExpired(
        Builder|Reimbursement|Relation $builder
    ): Builder|Reimbursement|Relation {
        return $builder->whereDoesntHave('voucher', fn(Builder $q) => VoucherQuery::whereExpired($q));
    }
}
