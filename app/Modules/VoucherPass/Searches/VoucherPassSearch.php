<?php

namespace App\Modules\VoucherPass\Searches;

use App\Models\Profile;
use App\Models\Record;
use App\Modules\VoucherPass\Models\VoucherPass;
use App\Searches\BaseSearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class VoucherPassSearch extends BaseSearch
{
    /**
     * @return Builder|Relation|VoucherPass
     */
    public function query(): Builder|Relation|VoucherPass
    {
        /** @var Builder|Relation|VoucherPass $builder */
        $builder = parent::query();
        $q = $this->getFilter('q');

        if (filled($q)) {
            $builder->where(function (Builder $query) use ($q) {
                $query->where('code', 'LIKE', '%' . $q . '%');
                $query->orWhere('external_id', 'LIKE', '%' . $q . '%');
                $query->orWhere('print_label', 'LIKE', '%' . $q . '%');

                foreach (['owner_identity', 'target_identity'] as $relation) {
                    $query->orWhereHas($relation . '.record_bsn', function (Builder|Record $query) use ($q) {
                        $query->where('value', 'LIKE', '%' . $q . '%');
                    });

                    $query->orWhereHas($relation . '.profiles', function (Builder|Profile $query) use ($q) {
                        $query->whereColumn('profiles.organization_id', 'voucher_passes.organization_id');

                        $query->whereHas('profile_records', function (Builder $query) use ($q) {
                            $query->whereNull('deleted_at');
                            $query->where('value', 'LIKE', '%' . $q . '%');

                            $query->whereHas('record_type', function (Builder $query) {
                                $query->whereIn('key', ['given_name', 'family_name']);
                            });
                        });
                    });
                }
            });
        }

        if ($state = $this->getFilter('state')) {
            $builder->where('state', $state);
        }

        if ($this->getFilter('print_requested') !== null) {
            $builder->where('print_requested', $this->getFilter('print_requested'));
        }

        return $builder->latest('id');
    }
}
