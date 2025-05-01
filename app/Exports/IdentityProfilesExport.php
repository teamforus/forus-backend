<?php

namespace App\Exports;

use App\Http\Requests\Api\Platform\Organizations\Sponsor\Identities\IndexIdentitiesRequest;
use App\Http\Resources\Sponsor\SponsorIdentityResource;
use App\Models\Identity;
use App\Models\Organization;
use App\Scopes\Builders\IdentityQuery;
use App\Searches\Sponsor\IdentitiesSearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class IdentityProfilesExport extends BaseFieldedExport
{
    protected Collection $data;
    protected array $fields;

    /**
     * @var array|string[]
     */
    protected static array $exportFields = [
        'id' => 'ID',
        'given_name' => 'Voornaam',
        'family_name' => 'Achternaam',
        'email' => 'E-mail adres',
        'bsn' => 'BSN',
        'client_number' => 'Klantnummer',
        'birth_date' => 'Geboorte datum',
        'last_activity' => 'Laatste inlog',
        'city' => 'Woonplaats',
        'street' => 'Straatnaam',
        'house_number' => 'Huisnummer',
        'house_number_addition' => 'Huisnummer toevoeging',
        'postal_code' => 'Postcode',
        'municipality_name' => 'Gemeentenaam',
        'neighborhood_name' => 'Woonwijk',
    ];

    /**
     * @param IndexIdentitiesRequest $request
     * @param Organization $organization
     * @param array $fields
     */
    public function __construct(IndexIdentitiesRequest $request, Organization $organization, array $fields)
    {
        $this->data = static::export($request, $organization, $fields);
        $this->fields = $fields;
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        $collection = $this->collection();

        return array_map(
            fn ($key) => static::$exportFields[$key] ?? $key,
            $collection->isNotEmpty() ? array_keys($collection->first()) : $this->fields
        );
    }

    public static function getExportFields(Organization $organization = null): array
    {
        return array_reduce(array_keys(static::$exportFields), function ($list, $key) use ($organization) {
            if (!$organization?->bsn_enabled && $key === 'bsn') {
                return $list;
            }

            return [...$list, [
                'key' => $key,
                'name' => static::$exportFields[$key],
            ]];
        }, []);
    }

    /**
     * @param IndexIdentitiesRequest $request
     * @param Organization $organization
     * @param array $fields
     * @return Collection
     */
    public static function export(
        IndexIdentitiesRequest $request,
        Organization $organization,
        array $fields,
    ): Collection {
        $search = new IdentitiesSearch([
            ...$request->only([
                'q', 'fund_id', 'birth_date_from', 'birth_date_to', 'postal_code', 'city', 'has_bsn',
                'municipality_name', 'last_activity_from', 'last_activity_to', 'last_login_from',
                'last_login_to', 'order_by', 'order_dir',
            ]),
            'organization_id' => $organization->id,
        ], IdentityQuery::relatedToOrganization(Identity::query(), $organization->id));

        return static::exportTransform($search->query(), $fields, $organization);
    }

    /**
     * @param Builder $builder
     * @param array $fields
     * @param Organization $organization
     * @return Collection
     */
    private static function exportTransform(
        Builder $builder,
        array $fields,
        Organization $organization,
    ): Collection {
        $fieldLabels = array_pluck(static::getExportFields($organization), 'name', 'key');
        $identities = $builder->with(SponsorIdentityResource::LOAD)->get();

        $data = $identities->map(function (Identity $identity) use ($organization, $fields) {
            $profile = $identity->profiles?->firstWhere('organization_id', $organization->id);
            $records = SponsorIdentityResource::getProfileRecords($profile, true);

            return array_only([
                'id' => $identity->id ?: '',
                'given_name' => Arr::get($records, 'given_name.0.value_locale', '-'),
                'family_name' => Arr::get($records, 'family_name.0.value_locale', '-'),
                'email' => $organization->email,
                'bsn' => $organization->bsn_enabled ? $identity->bsn ?: '-' : '-',
                'client_number' => Arr::get($records, 'client_number.0.value_locale', '-'),
                'birth_date' => Arr::get($records, 'birth_date.0.value_locale', '-'),
                'last_activity' => format_datetime_locale(array_first($identity->sessions)?->last_activity_at),
                'city' => Arr::get($records, 'city.0.value_locale', '-'),
                'street' => Arr::get($records, 'street.0.value_locale', '-'),
                'house_number' => Arr::get($records, 'house_number.0.value_locale', '-'),
                'house_number_addition' => Arr::get($records, 'house_number_addition.0.value_locale', '-'),
                'postal_code' => Arr::get($records, 'postal_code.0.value_locale', '-'),
                'amount_extra_cash' => Arr::get($records, 'amount_extra_cash.0.value_locale', '-'),
                'municipality_name' => Arr::get($records, 'municipality_name.0.value_locale', '-'),
                'neighborhood_name' => Arr::get($records, 'neighborhood_name.0.value_locale', '-'),
            ], $fields);
        })->values();

        return $data->map(function ($item) use ($fieldLabels) {
            return array_reduce(array_keys($item), fn ($obj, $key) => array_merge($obj, [
                $fieldLabels[$key] => $item[$key],
            ]), []);
        });
    }
}
