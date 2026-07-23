<?php

namespace App\Scopes\Builders;

use App\Models\FundRequestRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;

class FundRequestRecordQuery
{
    /**
     * @param Builder|Relation|FundRequestRecord $builder
     * @return Builder|Relation|FundRequestRecord
     */
    public static function whereIsFirstOfType(
        Builder|Relation|FundRequestRecord $builder,
    ): Builder|Relation|FundRequestRecord {
        return $builder->whereNotExists(function (QueryBuilder $builder) {
            $builder
                ->selectRaw('1')
                ->from('fund_request_records as earlier_fund_request_records')
                ->whereColumn('earlier_fund_request_records.fund_request_id', 'fund_request_records.fund_request_id')
                ->whereColumn('earlier_fund_request_records.record_type_key', 'fund_request_records.record_type_key')
                ->whereColumn('earlier_fund_request_records.id', '<', 'fund_request_records.id');
        });
    }
}
