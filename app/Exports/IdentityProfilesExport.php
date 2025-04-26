<?php

namespace App\Exports;

use App\Exports\Base\BaseFieldedExport;
use App\Http\Requests\Api\Platform\Organizations\Sponsor\Identities\IndexIdentitiesRequest;
use App\Http\Resources\Sponsor\SponsorIdentityResource;
use App\Models\Identity;
use App\Models\Organization;
use App\Scopes\Builders\IdentityQuery;
use App\Searches\Sponsor\IdentitiesSearch;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class IdentityProfilesExport extends BaseFieldedExport
{
    protected static string $transKey = 'identity_profiles';

    /**
     * @var array|string[]
     */
    protected static array $exportFields = [
        'id',
        'given_name',
        'family_name',
        'email',
        'bsn',
        'client_number',
        'birth_date',
        'last_activity',
        'city',
        'street',
        'house_number',
        'house_number_addition',
        'postal_code',
        'municipality_name',
        'neighborhood_name',
    ];

    /**
     * @param IndexIdentitiesRequest $request
     * @param Organization $organization
     * @param array $fields
     */
    public function __construct(
        IndexIdentitiesRequest $request,
        Organization $organization,
        protected array $fields
    ) {
        $this->data = $this->export($request, $organization);
    }

    /**
     * @param Organization|null $organization
     * @return array
     */
    public static function getExportFields(Organization $organization = null): array
    {
        return array_reduce(static::$exportFields, function ($list, $key) use ($organization) {
            if (!$organization?->bsn_enabled && $key === 'bsn') {
                return $list;
            }

            return [...$list, [
                'key' => $key,
                'name' => static::trans($key),
            ]];
        }, []);
    }

    /**
     * @param IndexIdentitiesRequest $request
     * @param Organization $organization
     * @return Collection
     */
    public function export(IndexIdentitiesRequest $request, Organization $organization): Collection
    {
        $search = new IdentitiesSearch([
            ...$request->only([
                'q', 'fund_id',
            ]),
            'organization_id' => $organization->id,
        ], IdentityQuery::relatedToOrganization(Identity::query(), $organization->id));

        $data = $search->query()->with(SponsorIdentityResource::LOAD)->get();

        return $this->exportTransform($data, $organization);
    }

    /**
     * @param Collection $data
     * @param Organization $organization
     * @return Collection
     */
    protected function transformKeysByOrganization(Collection $data, Organization $organization): Collection
    {
        $fieldLabels = array_pluck(static::getExportFields($organization), 'name', 'key');

        return $data->map(function ($item) use ($fieldLabels) {
            return array_reduce(array_keys($item), fn ($obj, $key) => array_merge($obj, [
                $fieldLabels[$key] => $item[$key],
            ]), []);
        });
    }

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @return array
     */
    protected function getRow(Identity $identity, Organization $organization): array
    {
        $profile = $identity->profiles?->firstWhere('organization_id', $organization->id);
        $records = SponsorIdentityResource::getProfileRecords($profile, true);

        return [
            'id' => $identity->id ?: '',
            'given_name' => Arr::get($records, 'given_name.0.value_locale', '-'),
            'family_name' => Arr::get($records, 'family_name.0.value_locale', '-'),
            'email' => $identity->email,
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
        ];
    }

    /**
     * @param Collection $data
     * @param Organization $organization
     * @return Collection
     */
    private function exportTransform(Collection $data, Organization $organization): Collection
    {
        $data = $data->map(fn (Identity $identity) => array_only(
            $this->getRow($identity, $organization),
            $this->fields
        ));

        return $this->transformKeysByOrganization($data, $organization);
    }
}
