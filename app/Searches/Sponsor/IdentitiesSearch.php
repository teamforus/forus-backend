<?php

namespace App\Searches\Sponsor;

use App\Models\Identity;
use App\Scopes\Builders\IdentityQuery;
use App\Searches\BaseSearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class IdentitiesSearch extends BaseSearch
{
    /**
     * @return Builder|null
     */
    public function query(): ?Builder
    {
        /** @var Builder|Identity $builder */
        $builder = parent::query();

        if ($this->getFilter('q')) {
            $builder = $this->querySearchIdentity($builder, $this->getFilter('q'));
        }

        if ($this->getFilter('organization_id')) {
            $builder = IdentityQuery::relatedToOrganization(
                $builder,
                $this->getFilter('organization_id'),
                $this->getFilter('fund_id'),
            );
        }

        if ($this->hasFilter('has_bsn')) {
            $has_bsn = $this->getFilter('has_bsn');

            if ($has_bsn) {
                $builder->has('record_bsn');
            }

            if (!is_null($has_bsn) && !$has_bsn) {
                $builder->doesntHave('record_bsn');
            }
        }

        if ($birth_date_from = $this->getFilter('birth_date_from')) {
            $builder->whereHas('profiles.profile_records', function (Builder $builder) use ($birth_date_from) {
                $builder->whereHas('record_type', fn (Builder $q) => $q->where('key', 'birth_date'));
                $builder->where('value', '>=', Carbon::parse($birth_date_from)->startOfDay());
            });
        }

        if ($birth_date_to = $this->getFilter('birth_date_to')) {
            $builder->whereHas('profiles.profile_records', function (Builder $builder) use ($birth_date_to) {
                $builder->whereHas('record_type', fn (Builder $q) => $q->where('key', 'birth_date'));
                $builder->where('value', '<=', Carbon::parse($birth_date_to)->endOfDay());
            });
        }

        if ($postal_code = $this->getFilter('postal_code')) {
            $builder->whereHas('profiles.profile_records', function (Builder $builder) use ($postal_code) {
                $builder->whereHas('record_type', fn (Builder $q) => $q->where('key', 'postal_code'));
                $builder->where('value', 'like', "%$postal_code%");
            });
        }

        if ($city = $this->getFilter('city')) {
            $builder->whereHas('profiles.profile_records', function (Builder $builder) use ($city) {
                $builder->whereHas('record_type', fn (Builder $q) => $q->where('key', 'city'));
                $builder->where('value', 'like', "%$city%");
            });
        }

        if ($municipality = $this->getFilter('municipality_name')) {
            $builder->whereHas('profiles.profile_records', function (Builder $builder) use ($municipality) {
                $builder->whereHas('record_type', fn (Builder $q) => $q->where('key', 'municipality_name'));
                $builder->where('value', 'like', "%$municipality%");
            });
        }

        if ($last_login_from = $this->getFilter('last_login_from')) {
            $builder->whereHas('session_last_login', function (Builder $builder) use ($last_login_from) {
                $builder->where('sessions.created_at', '>=', Carbon::parse($last_login_from)->startOfDay());
            });
        }

        if ($last_login_to = $this->getFilter('last_login_to')) {
            $builder->whereHas('session_last_login', function (Builder $builder) use ($last_login_to) {
                $builder->where('sessions.created_at', '<=', Carbon::parse($last_login_to)->endOfDay());
            });
        }

        if ($last_activity_from = $this->getFilter('last_activity_from')) {
            $builder->whereHas('session_last_activity', function (Builder $builder) use ($last_activity_from) {
                $builder->where('last_activity_at', '>=', Carbon::parse($last_activity_from)->startOfDay());
            });
        }

        if ($last_activity_to = $this->getFilter('last_activity_to')) {
            $builder->whereHas('session_last_activity', function (Builder $builder) use ($last_activity_to) {
                $builder->where('last_activity_at', '<=', Carbon::parse($last_activity_to)->endOfDay());
            });
        }

        return $builder->latest()->latest('id');
    }

    /**
     * @param Builder|Identity $builder
     * @param string $q
     * @return Builder|Identity
     */
    public function querySearchIdentity(Builder|Identity $builder, string $q): Builder|Identity
    {
        return $builder->where(function (Builder $builder) use ($q) {
            $builder->whereRelation('primary_email', 'email', 'like', "%$q%");
            $builder->orWhereRelation('emails_verified', 'email', 'like', "%$q%");
            $builder->orWhereRelation('record_bsn', 'value', 'like', "%$q%");
            $builder->orWhereHas('profiles.profile_records', function (Builder $builder) use ($q) {
                $builder->where('value', 'like', "%$q%");
                $builder->whereHas('record_type', fn (Builder $q) => $q->whereIn('key', [
                    'given_name', 'family_name', 'mobile', 'city', 'street', 'house_number', 'postal_code',
                    'client_number', 'municipality_name', 'neighborhood_name',
                ]));
            });
        });
    }
}
