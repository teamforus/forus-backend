<?php


namespace App\Searches\Sponsor;


use App\Models\Identity;
use App\Scopes\Builders\IdentityQuery;
use App\Searches\BaseSearch;
use Illuminate\Database\Eloquent\Builder;

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

        return $builder->latest();
    }

    /**
     * @param Builder|Identity $builder
     * @param string $q
     * @return Builder|Identity
     */
    public function querySearchIdentity(Builder|Identity $builder, string $q): Builder|Identity
    {
        return $builder->where(function(Builder $builder) use ($q) {
            $builder->whereRelation('primary_email', 'email', 'like', "%$q%");
            $builder->orWhereRelation('emails_verified', 'email', 'like', "%$q%");
            $builder->orWhereRelation('record_bsn', 'value', 'like', "%$q%");
            $builder->orWhereHas('profiles.profile_records', function(Builder $builder) use ($q) {
                $builder->where('value', 'like', "%$q%");
                $builder->whereHas('record_type', fn(Builder $q) => $q->whereIn('key', [
                    'given_name', 'family_name', 'mobile', 'city', 'street', 'house_number', 'postal_code',
                ]));
            });
        });
    }
}