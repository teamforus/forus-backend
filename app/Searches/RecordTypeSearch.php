<?php

namespace App\Searches;

use App\Models\RecordType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class RecordTypeSearch extends BaseSearch
{
    /**
     * @param array $filters
     * @param Builder|Relation|RecordType $builder
     */
    public function __construct(array $filters, Builder|Relation|RecordType $builder)
    {
        parent::__construct($filters, $builder);
    }

    /**
     * @return Builder|Relation|RecordType
     */
    public function query(): Builder|Relation|RecordType
    {
        /** @var Builder|Relation|RecordType $builder */
        $builder = parent::query()->with('translations');

        if ($this->getFilter('without_system')) {
            $builder->where('system', false);
        }

        if ($this->getFilter('vouchers')) {
            $builder->where('vouchers', true);
        }

        if ($this->getFilter('criteria')) {
            $builder->where('criteria', true);
        }

        if ($this->getFilter('organization_id')) {
            $builder->where(function (Builder|RecordType $builder) {
                $builder->whereNull('organization_id');
                $builder->orWhere('organization_id', $this->getFilter('organization_id'));
            });
        }

        return $builder;
    }
}
