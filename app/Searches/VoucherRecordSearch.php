<?php

namespace App\Searches;

use App\Models\RecordType;
use App\Models\RecordTypeTranslation;
use App\Models\VoucherRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class VoucherRecordSearch extends BaseSearch
{
    /**
     * @return Builder|Relation
     */
    public function query(): Builder|Relation
    {
        $builder = $this->appendSortColumns(parent::getBuilder());

        if ($q = $this->getFilter('q')) {
            $builder->where(function(Builder $builder) use ($q) {
                $builder->where('value', 'LIKE', "%$q%");
                $builder->orWhere('note', 'LIKE', "%$q%");
                $builder->orWhere('record_type_name', 'LIKE', "%$q%");
            });
        }

        return $builder->orderBy(
            $this->getFilter('order_by', 'created_at'),
            $this->getFilter('order_dir', 'desc'),
        );
    }

    /**
     * @param Builder|Relation $builder
     * @return Builder|Relation|VoucherRecord
     */
    protected function appendSortColumns(Builder|Relation $builder): Builder|Relation|VoucherRecord
    {
        $builder->addSelect([
            'record_type_name' => RecordTypeTranslation::query()
                ->whereColumn('record_type_translations.record_type_id', 'voucher_records.record_type_id')
                ->where('locale', app()->getLocale())
                ->limit(1)
                ->select('record_type_translations.name'),
        ]);

        return VoucherRecord::query()->fromSub($builder, 'voucher_records');
    }
}