<?php

namespace App\Searches\Sponsor;

use App\Models\Identity;
use App\Models\IdentityEmail;
use App\Models\ProfileRecord;
use App\Models\Record;
use App\Scopes\Builders\IdentityQuery;
use App\Searches\BaseSearch;
use App\Services\Forus\Session\Models\Session;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class IdentitiesSearch extends BaseSearch
{
    public const array SORT_BY_RECORD_TYPE = [
        'given_name', 'family_name', 'client_number', 'birth_date',
        'city', 'street', 'house_number', 'house_number_addition', 'postal_code', 'municipality_name',
        'neighborhood_name',
    ];

    public const array SORT_BY = [
        'id', 'email', 'bsn',  'last_activity', 'last_login', 'created_at',
        ...self::SORT_BY_RECORD_TYPE,
    ];

    /**
     * @return Builder|null
     */
    public function query(): ?Builder
    {
        /** @var Builder|Identity $builder */
        $builder = parent::query();

        $orderBy = $this->getFilter('order_by', 'created_at');
        $orderDir = $this->getFilter('order_dir', 'desc');
        $fundId = $this->getFilter('fund_id');
        $organizationId = $this->getFilter('organization_id');

        if (empty($organizationId) || !is_numeric($organizationId)) {
            throw new InvalidArgumentException("Invalid organizationId field: $organizationId");
        }

        if (!in_array($orderBy, self::SORT_BY)) {
            throw new InvalidArgumentException("Invalid sort field: $orderBy");
        }

        $builder = IdentityQuery::relatedToOrganization($builder, $organizationId, $fundId);

        if ($this->getFilter('q')) {
            $builder = $this->querySearchIdentity($builder, $this->getFilter('q'), $organizationId);
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

        if ($birthDateFrom = $this->getFilter('birth_date_to')) {
            $builder->whereHas('profiles', function (Builder $builder) use ($birthDateFrom, $organizationId) {
                $builder->where('organization_id', $organizationId);

                $builder->whereHas('profile_records', function (Builder $builder) use ($birthDateFrom) {
                    $builder->whereHas('record_type', fn (Builder $q) => $q->where('key', 'birth_date'));
                    $builder->where('value', '>=', Carbon::parse($birthDateFrom)->startOfDay());
                });
            });
        }

        if ($birthDateTo = $this->getFilter('birth_date_to')) {
            $builder->whereHas('profiles', function (Builder $builder) use ($birthDateTo, $organizationId) {
                $builder->where('organization_id', $organizationId);

                $builder->whereHas('profile_records', function (Builder $builder) use ($birthDateTo) {
                    $builder->whereHas('record_type', fn (Builder $q) => $q->where('key', 'birth_date'));
                    $builder->where('value', '<=', Carbon::parse($birthDateTo)->endOfDay());
                });
            });
        }

        if ($postalCode = $this->getFilter('postal_code')) {
            $builder->whereHas('profiles', function (Builder $builder) use ($postalCode, $organizationId) {
                $builder->where('organization_id', $organizationId);

                $builder->whereHas('profile_records', function (Builder $builder) use ($postalCode) {
                    $builder->whereHas('record_type', fn (Builder $q) => $q->where('key', 'postal_code'));
                    $builder->where('value', 'like', "%$postalCode%");
                });
            });
        }

        if ($city = $this->getFilter('city')) {
            $builder->whereHas('profiles', function (Builder $builder) use ($city, $organizationId) {
                $builder->where('organization_id', $organizationId);

                $builder->whereHas('profile_records', function (Builder $builder) use ($city) {
                    $builder->whereHas('record_type', fn (Builder $q) => $q->where('key', 'city'));
                    $builder->where('value', 'like', "%$city%");
                });
            });
        }

        if ($municipality = $this->getFilter('municipality_name')) {
            $builder->whereHas('profiles', function (Builder $builder) use ($municipality, $organizationId) {
                $builder->where('organization_id', $organizationId);

                $builder->whereHas('profile_records', function (Builder $builder) use ($municipality) {
                    $builder->whereHas('record_type', fn (Builder $q) => $q->where('key', 'municipality_name'));
                    $builder->where('value', 'like', "%$municipality%");
                });
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

        return $this->order($builder, $organizationId, $orderBy, $orderDir);
    }

    public function order(
        Builder $builder,
        int $organizationId,
        string $orderBy = 'created_at',
        string $orderDir = 'desc',
    ): Builder {
        if (in_array($orderBy, self::SORT_BY_RECORD_TYPE)) {
            $builder->addSelect([
                '__sort' => ProfileRecord::query()
                    ->whereHas('profile', function (Builder $builder) use ($organizationId) {
                        $builder->whereColumn('identity_id', 'identities.id');
                        $builder->where('organization_id', $organizationId);
                    })
                    ->whereRelation('record_type', 'key', $orderBy)
                    ->latest('created_at')
                    ->select('value'),
            ]);

            return Identity::query()->fromSub($builder, 'list')->orderBy('__sort', $orderDir);
        }

        if ($orderBy == 'last_activity') {
            $builder->addSelect([
                '__sort' => Session::query()
                    ->whereColumn('identity_address', 'address')
                    ->latest('last_activity_at')
                    ->select('last_activity_at'),
            ]);

            return Identity::query()->fromSub($builder, 'list')->orderBy('__sort', $orderDir);
        }

        if ($orderBy == 'last_login') {
            $builder->addSelect([
                '__sort' => Session::query()
                    ->whereColumn('identity_address', 'address')
                    ->latest('created_at')
                    ->select('created_at'),
            ]);

            return Identity::query()->fromSub($builder, 'list')->orderBy('__sort', $orderDir);
        }

        if ($orderBy == 'email') {
            $builder->addSelect([
                '__sort' => IdentityEmail::query()
                    ->whereColumn('identity_address', 'address')
                    ->where('primary', true)
                    ->latest()->select('email'),
            ]);

            return Identity::query()->fromSub($builder, 'list')->orderBy('__sort', $orderDir);
        }

        if ($orderBy == 'bsn') {
            $builder->addSelect([
                '__sort' => Record::where(function (Builder $builder) use ($orderBy) {
                    $builder->whereColumn('identity_address', 'address');
                    $builder->whereRelation('record_type', 'key', 'bsn');
                })->latest('created_at')->select('value'),
            ]);

            return Identity::query()->fromSub($builder, 'list')->orderBy('__sort', $orderDir);
        }

        return $builder->orderBy($orderBy, $orderDir);
    }

    /**
     * @param Builder|Identity $builder
     * @param string $q
     * @param int $organizationId
     * @return Builder|Identity
     */
    public function querySearchIdentity(Builder|Identity $builder, string $q, int $organizationId): Builder|Identity
    {
        return $builder->where(function (Builder $builder) use ($q, $organizationId) {
            $builder->whereRelation('primary_email', 'email', 'like', "%$q%");
            $builder->orWhereRelation('emails_verified', 'email', 'like', "%$q%");
            $builder->orWhereRelation('record_bsn', 'value', 'like', "%$q%");

            $builder->orWhereHas('profiles', function (Builder $builder) use ($q, $organizationId) {
                $builder->where('organization_id', $organizationId);

                $builder->whereHas('profile_records', function (Builder $builder) use ($q) {
                    $builder->where('value', 'like', "%$q%");
                    $builder->whereHas('record_type', fn (Builder $q) => $q->whereIn('key', [
                        'given_name', 'family_name', 'mobile', 'city', 'street', 'house_number', 'postal_code',
                        'client_number', 'municipality_name', 'neighborhood_name',
                    ]));
                });
            });
        });
    }
}
