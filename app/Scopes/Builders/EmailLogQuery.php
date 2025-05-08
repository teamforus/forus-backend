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
use App\Mail\Vouchers\VoucherAssignedSubsidyMail;
use App\Mail\Vouchers\VoucherExpireSoonBudgetMail;
use App\Models\FundRequest;
use App\Models\FundRequestRecord;
use App\Models\Identity;
use App\Models\ProductReservation;
use App\Models\Reimbursement;
use App\Models\Voucher;
use App\Services\EventLogService\Models\EventLog;
use App\Services\MailDatabaseLoggerService\Models\EmailLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class EmailLogQuery
{
    /**
     * @param Builder|Relation|EmailLog $builder
     * @param FundRequest $fundRequest
     * @return Builder|Relation|EmailLog
     */
    public static function whereFundRequest(
        Builder|Relation|EmailLog $builder,
        FundRequest $fundRequest,
    ): Builder|Relation|EmailLog {
        return $builder->whereIn('mailable', [
            FundRequestDeniedMail::class,
            FundRequestCreatedMail::class,
            FundRequestApprovedMail::class,
            FundRequestDisregardedMail::class,
            FundRequestClarificationRequestedMail::class,
        ])->whereHas('event_log', fn (Builder $builder) => static::eventsOfTypeFundRequestQuery(
            $builder,
            FundRequest::where('id', $fundRequest->id),
        ));
    }

    /**
     * @param Builder|Relation|EmailLog $builder
     * @param Identity $identity
     * @return Builder|Relation|EmailLog
     */
    public static function whereIdentity(
        Builder|Relation|EmailLog $builder,
        Identity $identity,
    ): Builder|Relation|EmailLog {
        return $builder->whereIn('mailable', [
            // Voucher
            VoucherAssignedBudgetMail::class,
            VoucherAssignedSubsidyMail::class,
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
        ])->whereHas('event_log', function (Builder $builder) use ($identity) {
            $builder->where(fn (Builder $builder) => EventLog::eventsOfTypeQuery(
                Voucher::class,
                $identity->vouchers(),
                $builder,
            ));

            $builder->orWhere(fn (Builder $builder) => EventLog::eventsOfTypeQuery(
                ProductReservation::class,
                ProductReservation::whereIn('id', $identity->vouchers()->select('product_reservation_id')),
                $builder,
            ));

            $builder->orWhere(fn (Builder $builder) => EventLog::eventsOfTypeQuery(
                Reimbursement::class,
                Reimbursement::whereIn('voucher_id', $identity->vouchers()->select('id')),
                $builder,
            ));

            $builder->orWhere(fn (Builder $builder) => static::eventsOfTypeFundRequestQuery(
                $builder,
                $identity->fund_requests(),
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
     * @param Builder|Relation $loggable
     * @return Builder|Relation
     */
    protected static function eventsOfTypeFundRequestQuery(
        Builder|Relation $builder,
        Builder|Relation $loggable,
    ): Builder|Relation {
        return $builder->where(function (Builder $builder) use ($loggable) {
            $builder->where(fn (Builder $builder) => EventLog::eventsOfTypeQuery(
                FundRequest::class,
                $loggable,
                $builder,
            ));

            $recordIds = FundRequestRecord::whereIn('fund_request_id', $loggable->select('id'));

            $builder->orWhere(fn (Builder $builder) => EventLog::eventsOfTypeQuery(
                FundRequestRecord::class,
                $recordIds,
                $builder,
            ));
        });
    }
}
