<?php

namespace App\Modules\DCP\Searches;

use App\Modules\DCP\Models\DCPOrderBulk;
use App\Searches\BaseSearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class DCPOrderBulkSearch extends BaseSearch
{
    /**
     * @return Builder|Relation|DCPOrderBulk
     */
    public function query(): Builder|Relation|DCPOrderBulk
    {
        /** @var Builder|Relation|DCPOrderBulk $query */
        $query = parent::query();
        $q = $this->getFilter('q');

        if (filled($q)) {
            $query->where(function (Builder $query) use ($q) {
                $query->where('id', 'LIKE', '%' . $q . '%');
                $query->orWhereHas('employee.identity.primary_email', function (Builder $query) use ($q) {
                    $query->where('email', 'LIKE', '%' . $q . '%');
                });
            });
        }

        return $query;
    }
}
