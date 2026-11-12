<?php

namespace App\Modules\RequesterImport\Searches;

use App\Modules\RequesterImport\Models\RequesterImportRow;
use App\Searches\BaseSearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class RequesterImportRowsSearch extends BaseSearch
{
    /**
     * @return Builder|Relation|RequesterImportRow
     */
    public function query(): Builder|Relation|RequesterImportRow
    {
        /** @var Builder|Relation|RequesterImportRow $builder */
        $builder = parent::query();
        $q = $this->getFilter('q');

        if (filled($q)) {
            $builder->where(function (Builder $query) use ($q) {
                $query
                    ->where('bsn', 'LIKE', '%' . $q . '%')
                    ->orWhereHas('records', fn (Builder $query) => $query->where('value', 'LIKE', '%' . $q . '%'));
            });
        }

        return $builder->latest('id');
    }
}
