<?php


namespace App\Scopes\Builders;

use App\Models\Fund;
use App\Models\Identity;
use App\Models\IdentityEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class IdentityQuery
{
    /**
     * @param Relation|Builder $builder
     * @param Fund $fund
     * @param bool $withReservations
     * @return Relation|Builder
     */
    public static function appendVouchersCountFields(
        Relation|Builder $builder,
        Fund $fund,
        bool $withReservations = false
    ): Relation|Builder {
        $vouchersQuery = $fund
            ->vouchers()
            ->selectRaw('count(*)')
            ->whereColumn('vouchers.identity_address', 'identities.address');

        if (!$withReservations) {
            $vouchersQuery->whereNull('product_reservation_id');
        }

        return $builder->addSelect([
            'count_vouchers' => clone $vouchersQuery,
            'count_vouchers_active' => VoucherQuery::whereNotExpiredAndActive(clone $vouchersQuery),
            'count_vouchers_active_with_balance' => VoucherQuery::whereHasBalanceIsActiveAndNotExpired(clone $vouchersQuery),
        ]);
    }

    /**
     * @param Relation|Builder $builder
     * @return Relation|Builder
     */
    public static function appendEmailField(Relation|Builder $builder): Relation|Builder
    {
        return $builder->addSelect([
            'email' => IdentityEmail::query()
                ->whereColumn('identities.address', 'identity_emails.identity_address')
                ->where('primary', true)
                ->select('email'),
        ]);
    }

    /**
     * @param Builder|Relation|Identity $builder
     * @param string $bsn
     * @return Builder|Relation|Identity
     */
    public static function whereBsn(
        Builder|Relation|Identity $builder,
        string $bsn,
    ): Builder|Relation|Identity  {
        return $builder->whereHas('records', function(Builder $builder) use ($bsn) {
            $builder->where('value', '=', $bsn);
            $builder->whereRelation('record_type', 'record_types.key', '=', 'bsn');
        });
    }

    /**
     * @param Builder|Relation|Identity $builder
     * @param string $bsn
     * @return Builder|Relation|Identity
     */
    public static function whereBsnLike(
        Builder|Relation|Identity $builder,
        string $bsn,
    ): Builder|Relation|Identity  {
        return $builder->whereHas('records', function(Builder $builder) use ($bsn) {
            $builder->where('value', 'LIKE', "%$bsn%");
            $builder->whereRelation('record_type', 'record_types.key', '=', 'bsn');
        });
    }

    /**
     * @param Builder|Relation|Identity $builder
     * @param array|int $organizationId
     * @param array|int|null $fundId
     * @return Builder|Relation|Identity
     */
    public static function relatedToOrganization(
        Builder|Relation|Identity $builder,
        array|int $organizationId,
        array|int $fundId = null,
    ): Builder|Relation|Identity  {
        return $builder->where(function(Builder $builder) use ($organizationId, $fundId) {
            $builder->whereHas('vouchers.fund', function (Builder $builder) use ($organizationId, $fundId) {
                $builder->whereIn('organization_id', (array) $organizationId);

                if ($fundId) {
                    $builder->whereIn('id', (array) $fundId);
                }
            });

            $builder->orWhereHas('fund_requests.fund', function (Builder $builder) use ($organizationId, $fundId) {
                $builder->whereIn('organization_id', (array) $organizationId);

                if ($fundId) {
                    $builder->whereIn('id', (array) $fundId);
                }
            });
        });
    }
}
