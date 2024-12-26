<?php

namespace App\Scopes\Builders;

use App\Mail\Funds\FundRequestClarifications\FundRequestClarificationRequestedMail;
use App\Mail\Funds\FundRequests\FundRequestApprovedMail;
use App\Mail\Funds\FundRequests\FundRequestCreatedMail;
use App\Mail\Funds\FundRequests\FundRequestDeniedMail;
use App\Mail\Funds\FundRequests\FundRequestDisregardedMail;
use App\Models\FundRequest;
use App\Models\FundRequestRecord;
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
        ])->whereHas('event_log', function (Builder $builder) use ($fundRequest) {
            $builder->where(function (Builder $builder) use ($fundRequest) {
                $builder->where('loggable_type', 'fund_request');
                $builder->where('loggable_id', $fundRequest->id);
            });

            $builder->orWhere(function (Builder $builder) use ($fundRequest) {
                $recordIds = FundRequestRecord::query()
                    ->where('fund_request_id', $fundRequest->id)
                    ->select('id');

                $builder->where('loggable_type', 'fund_request_record');
                $builder->whereIn('loggable_id', $recordIds);
            });
        });
    }

    /**
     * @param Builder|Relation|EmailLog $query
     * @param string $q
     * @return Builder|Relation
     */
    public static function whereQueryFilter(Builder|Relation|EmailLog $query, string $q): Builder|Relation
    {
        return $query->where(function(Builder $builder) use ($q) {
            $builder->where('subject', 'LIKE', "%$q%");
            $builder->orWhere('from_name', 'LIKE', "%$q%");
            $builder->orWhere('from_address', 'LIKE', "%$q%");
            $builder->orWhere('to_name', 'LIKE', "%$q%");
            $builder->orWhere('to_address', 'LIKE', "%$q%");
            $builder->orWhere('content', 'LIKE', "%$q%");
        });
    }
}
