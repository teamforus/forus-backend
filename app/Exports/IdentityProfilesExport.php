<?php

namespace App\Exports;

use App\Exports\Base\BaseExport;
use App\Http\Resources\Sponsor\SponsorIdentityResource;
use App\Models\Identity;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class IdentityProfilesExport extends BaseExport
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
     * @param Builder|Relation|Model $builder
     * @param array $fields
     * @param Organization $organization
     */
    public function __construct(
        Builder|Relation|Model $builder,
        protected array $fields,
        protected Organization $organization,
    ) {
        parent::__construct($builder, $fields);
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
     * @param Collection $data
     * @return Collection
     */
    protected function transformKeys(Collection $data): Collection
    {
        $fieldLabels = array_pluck(static::getExportFields($this->organization), 'name', 'key');

        return $data->map(function ($item) use ($fieldLabels) {
            return array_reduce(array_keys($item), fn ($obj, $key) => array_merge($obj, [
                $fieldLabels[$key] => $item[$key],
            ]), []);
        });
    }

    /**
     * @param Model|Identity $model
     * @return array
     */
    protected function getRow(Model|Identity $model): array
    {
        $profile = $model->profiles?->firstWhere('organization_id', $this->organization->id);
        $records = SponsorIdentityResource::getProfileRecords($profile, true);

        return [
            'id' => $model->id ?: '',
            'given_name' => Arr::get($records, 'given_name.0.value_locale', '-'),
            'family_name' => Arr::get($records, 'family_name.0.value_locale', '-'),
            'email' => $model->email,
            'bsn' => $this->organization->bsn_enabled ? $model->bsn ?: '-' : '-',
            'client_number' => Arr::get($records, 'client_number.0.value_locale', '-'),
            'birth_date' => Arr::get($records, 'birth_date.0.value_locale', '-'),
            'last_activity' => format_datetime_locale(array_first($model->sessions)?->last_activity_at),
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
     * @return array
     */
    protected function getBuilderWithArray(): array
    {
        return SponsorIdentityResource::LOAD;
    }
}
