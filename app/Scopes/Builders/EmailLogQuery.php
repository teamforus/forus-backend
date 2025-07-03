<?php

namespace App\Scopes\Builders;

use App\Mail\Funds\FundRequestClarifications\FundRequestClarificationRequestedMail;
use App\Mail\Funds\FundRequests\FundRequestApprovedMail;
use App\Mail\Funds\FundRequests\FundRequestCreatedMail;
use App\Mail\Funds\FundRequests\FundRequestDeniedMail;
use App\Mail\Funds\FundRequests\FundRequestDisregardedMail;
use App\Mail\ProductReservations\ProductReservationAcceptedMail;
use App\Mail\ProductReservations\ProductReservationCanceledMail;
use App\Mail\ProductReservations\ProductReservationRejectedMail;
use App\Mail\Reimbursements\ReimbursementApprovedMail;
use App\Mail\Reimbursements\ReimbursementDeclinedMail;
use App\Mail\Reimbursements\ReimbursementSubmittedMail;
use App\Mail\Vouchers\DeactivationVoucherMail;
use App\Mail\Vouchers\PaymentSuccessBudgetMail;
use App\Mail\Vouchers\RequestPhysicalCardMail;
use App\Mail\Vouchers\VoucherAssignedBudgetMail;
use App\Mail\Vouchers\VoucherAssignedProductMail;
use App\Mail\Vouchers\VoucherExpireSoonBudgetMail;
use App\Models\FundRequest;
use App\Models\FundRequestRecord;
use App\Models\Identity;
use App\Models\Organization;
use App\Models\ProductReservation;
use App\Models\Reimbursement;
use App\Models\Voucher;
use App\Services\EventLogService\Models\EventLog;
use App\Services\MailDatabaseLoggerService\Models\EmailLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QBuilder;

class EmailLogQuery
{
    /**
     * @param Builder|Relation|EmailLog $builder
     * @param FundRequest $fundRequest
     * @param ?Organization $organization
     * @return Builder|Relation|EmailLog
     */
    public static function whereFundRequest(
        Builder|Relation|EmailLog $builder,
        FundRequest $fundRequest,
        ?Organization $organization,
    ): Builder|Relation|EmailLog {
        $fundRequestsQuery = FundRequest::query()
            ->where('id', $fundRequest->id)
            ->whereRelation('fund', 'organization_id', $organization->id);

        return $builder->whereIn('mailable', [
            FundRequestDeniedMail::class,
            FundRequestCreatedMail::class,
            FundRequestApprovedMail::class,
            FundRequestDisregardedMail::class,
            FundRequestClarificationRequestedMail::class,
        ])->whereHas('event_log', fn (Builder $builder) => static::eventsOfTypeFundRequestQuery(
            $builder,
            $fundRequestsQuery,
        ));
    }

    /**
     * @param Builder|Relation|EmailLog $builder
     * @param Identity $identity
     * @param ?Organization $organization
     * @return Builder|Relation|EmailLog
     */
    public static function whereIdentity(
        Builder|Relation|EmailLog $builder,
        Identity $identity,
        ?Organization $organization,
    ): Builder|Relation|EmailLog {
        return $builder->whereIn('mailable', [
            // Voucher
            VoucherAssignedBudgetMail::class,
            VoucherAssignedProductMail::class,
            DeactivationVoucherMail::class,
            VoucherExpireSoonBudgetMail::class,
            RequestPhysicalCardMail::class,
            PaymentSuccessBudgetMail::class,

            // ProductReservation
            ProductReservationAcceptedMail::class,
            ProductReservationCanceledMail::class,
            ProductReservationRejectedMail::class,

            // Reimbursement
            ReimbursementSubmittedMail::class,
            ReimbursementApprovedMail::class,
            ReimbursementDeclinedMail::class,

            // FundRequest
            FundRequestDeniedMail::class,
            FundRequestCreatedMail::class,
            FundRequestApprovedMail::class,
            FundRequestDisregardedMail::class,
            FundRequestClarificationRequestedMail::class,
        ])->whereHas('event_log', function (Builder $builder) use ($identity, $organization) {
            $vouchersQuery = $identity->vouchers();
            $fundRequestsQuery = $identity->fund_requests();
            $reservationsQuery = $identity->product_reservations();
            $reimbursementsQuery = $identity->reimbursements();

            if ($organization) {
                $vouchersQuery->whereRelation('fund', 'organization_id', $organization->id);
                $reservationsQuery->whereRelation('voucher.fund', 'organization_id', $organization->id);
                $fundRequestsQuery->whereRelation('fund', 'organization_id', $organization->id);
                $reimbursementsQuery->whereRelation('voucher.fund', 'organization_id', $organization->id);
            }

            $builder->where(fn (Builder $builder) => EventLog::eventsOfTypeQuery(
                Voucher::class,
                $vouchersQuery->pluck('vouchers.id')->unique()->toArray(),
                $builder,
            ));

            $builder->orWhere(fn (Builder $builder) => EventLog::eventsOfTypeQuery(
                ProductReservation::class,
                $reservationsQuery->pluck('product_reservations.id')->unique()->toArray(),
                $builder,
            ));

            $builder->orWhere(fn (Builder $builder) => EventLog::eventsOfTypeQuery(
                Reimbursement::class,
                $reimbursementsQuery->pluck('reimbursements.id')->unique()->toArray(),
                $builder,
            ));

            $builder->orWhere(fn (Builder $builder) => static::eventsOfTypeFundRequestQuery(
                $builder,
                $fundRequestsQuery->pluck('fund_requests.id')->unique()->toArray(),
            ));
        });
    }

    /**
     * @param Builder|Relation|EmailLog $query
     * @param string $q
     * @return Builder|Relation
     */
    public static function whereQueryFilter(Builder|Relation|EmailLog $query, string $q): Builder|Relation
    {
        return $query->where(function (Builder $builder) use ($q) {
            $builder->where('subject', 'LIKE', "%$q%");
            $builder->orWhere('from_name', 'LIKE', "%$q%");
            $builder->orWhere('from_address', 'LIKE', "%$q%");
            $builder->orWhere('to_name', 'LIKE', "%$q%");
            $builder->orWhere('to_address', 'LIKE', "%$q%");
            $builder->orWhere('content', 'LIKE', "%$q%");
        });
    }

    /**
     * @param Builder|Relation $builder
     * @param Builder|Relation|int|array $loggable
     * @return Builder|Relation
     */
    protected static function eventsOfTypeFundRequestQuery(
        Builder|Relation $builder,
        mixed $loggable,
    ): Builder|Relation {
        return $builder->where(function (Builder $builder) use ($loggable) {
            $builder->where(fn (Builder $builder) => EventLog::eventsOfTypeQuery(
                FundRequest::class,
                $loggable,
                $builder,
            ));

            if ($loggable instanceof Builder ||
                $loggable instanceof QBuilder ||
                $loggable instanceof Relation) {
                $recordIds = FundRequestRecord::whereIn('fund_request_id', $loggable->select('id'));
            } else {
                $recordIds = FundRequestRecord::whereIn('fund_request_id', (array) $loggable)->pluck('id')->all();
            }

            $builder->orWhere(fn (Builder $builder) => EventLog::eventsOfTypeQuery(
                FundRequestRecord::class,
                $recordIds,
                $builder,
            ));
        });
    }
}
