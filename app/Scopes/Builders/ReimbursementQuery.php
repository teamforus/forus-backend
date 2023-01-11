<?php


namespace App\Scopes\Builders;

use App\Models\Reimbursement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

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
        return $builder->whereNotIn('id', self::whereExpired(Reimbursement::query())->select('id'));
    }

    /**
     * Deactivated are considered reimbursement that are related to deactivated vouchers
     * @param Builder|Reimbursement|Relation $builder
     * @return Builder|Reimbursement|Relation
     */
    public static function whereDeactivated(
        Builder|Reimbursement|Relation $builder
    ): Builder|Reimbursement|Relation {
        return $builder->whereHas('voucher', fn(Builder $b) => VoucherQuery::whereDeactivated($b));
    }

    /**
     * Deactivated are considered reimbursement that are related to deactivated vouchers
     * @param Builder|Reimbursement|Relation $builder
     * @return Builder|Reimbursement|Relation
     */
    public static function whereNotDeactivated(
        Builder|Reimbursement|Relation $builder
    ): Builder|Reimbursement|Relation {
        return $builder->where(function(Builder $builder) {
            $builder->whereNotIn('id', self::whereDeactivated(Reimbursement::query())->select('id'));
        });
    }

    /**
     * Archived are considered reimbursement that are related to expired or deactivated vouchers
     * @param Builder|Reimbursement|Relation $builder
     * @return Builder|Reimbursement|Relation
     */
    public static function whereArchived(
        Builder|Reimbursement|Relation $builder
    ): Builder|Reimbursement|Relation {
        return $builder->where(function(Builder $builder) {
            $builder->where(fn(Builder $b) => self::whereDeactivated($b));
            $builder->orWhere(fn(Builder $b) => self::whereExpired($b));
        });
    }

    /**
     * Not archived are considered reimbursement that are not related to expired or deactivated vouchers
     * @param Builder|Reimbursement|Relation $builder
     * @return Builder|Reimbursement|Relation
     */
    public static function whereNotArchived(
        Builder|Reimbursement|Relation $builder
    ): Builder|Reimbursement|Relation {
        return $builder->whereNotIn('id', self::whereArchived(Reimbursement::query())->select('id'));
    }
}
