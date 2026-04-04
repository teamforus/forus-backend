<?php

namespace App\Scopes\Builders;

use App\Models\Fund;
use App\Models\FundRequest;
use App\Models\Identity;
use App\Models\IdentityEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class IdentityQuery
{
    /**
     * @param Builder|Relation|Identity $builder
     * @param Fund $fund
     * @param bool $withReservations
     * @return Builder|Relation|Identity
     */
    public static function appendVouchersCountFields(
        Builder|Relation|Identity $builder,
        Fund $fund,
        bool $withReservations = false,
    ): Builder|Relation|Identity {
        $vouchersQuery = $fund
            ->vouchers()
            ->selectRaw('count(*)')
            ->whereColumn('vouchers.identity_id', 'identities.id');

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
     * @param Builder|Relation|Identity $builder
     * @return Builder|Relation|Identity
     */
    public static function appendEmailField(
        Builder|Relation|Identity $builder,
    ): Builder|Relation|Identity {
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
    ): Builder|Relation|Identity {
        return $builder->whereHas('records', function (Builder $builder) use ($bsn) {
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
    ): Builder|Relation|Identity {
        return $builder->whereHas('records', function (Builder $builder) use ($bsn) {
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
    ): Builder|Relation|Identity {
        return $builder->where(function (Builder $builder) use ($organizationId, $fundId) {
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

            $builder->orWhere(function (Builder $builder) use ($organizationId) {
                $builder->where('type', Identity::TYPE_PROFILE);
                $builder->where('creator_organization_id', (array) $organizationId);
            });
        });
    }

    /**
     * @param Builder|Relation|Identity $builder
     * @param Identity $identity
     * @param Fund $fund
     * @return Builder|Relation|Identity
     */
    public static function whereHasPartnerBsnRecord(
        Builder|Relation|Identity $builder,
        Identity $identity,
        Fund $fund,
    ): Builder|Relation|Identity {
        return $builder->whereHas('records', function (Builder $builder) use ($identity, $fund) {
            $builder->where(function (Builder $builder) use ($identity, $fund) {
                $builder->whereRelation('record_type', 'record_types.key', '=', 'partner_bsn');
                $builder->whereIn('value', array_filter([$identity?->bsn]));

                $builder->whereHas('validations', function (Builder $query) use ($fund) {
                    $startDate = $fund->fund_config?->record_validity_start_date;
                    $trustedDays = $fund->getTrustedDays('partner_bsn');

                    RecordValidationQuery::whereStillTrustedQuery($query, $trustedDays, $startDate);
                    RecordValidationQuery::whereTrustedByQuery($query, $fund);
                });
            });

            $builder->orWhere(function (Builder $builder) use ($identity, $fund) {
                $builder->whereRelation('record_type', 'record_types.key', '=', 'bsn');
                $builder->whereIn('value', array_filter([
                    $fund->getTrustedRecordOfType($identity, 'partner_bsn')?->value,
                ]));
            });
        });
    }

    /**
     * @param Builder|Relation|Identity $builder
     * @param Identity $identity
     * @param Fund $fund
     * @return Builder|Relation|Identity
     */
    public static function whereHasPendingFundRequestPartnerBsnRecord(
        Builder|Relation|Identity $builder,
        Identity $identity,
        Fund $fund,
    ): Builder|Relation|Identity {
        return $builder->whereHas('fund_requests', function (Builder $builder) use ($identity, $fund) {
            $builder->where('fund_id', $fund->id);
            $builder->where('state', FundRequest::STATE_PENDING);

            $builder->whereHas('records', function (Builder $builder) use ($identity) {
                $builder->where('record_type_key', 'partner_bsn');
                $builder->whereIn('value', array_filter([$identity->bsn]));
            });
        });
    }
}
