<?php

namespace App\Services\IConnectApiService;

use App\Models\Fund;
use App\Models\FundCriterion;
use App\Models\Identity;
use App\Models\PersonBsnApiRecordType;
use App\Services\IConnectApiService\Exceptions\PersonBsnApiIsTakenByPartnerException;
use App\Services\IConnectApiService\Objects\Person;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;

class IConnectPrefill
{
    public const string PREFILL_ERROR_NOT_FOUND = 'not_found';
    public const string PREFILL_ERROR_CONNECTION_ERROR = 'connection_error';
    public const string PREFILL_ERROR_NOT_FILLED_REQUIRED_CRITERIA = 'not_filled_required_criteria';
    public const string PREFILL_ERROR_TAKEN_BY_PARTNER = 'taken_by_partner';

    public const string GENDER_FEMALE = 'vrouw';

    protected array $infoMissedFields = [];
    protected array $warningMissedFields = [];

    /**
     * @param Fund $fund
     * @param string $bsn
     * @param bool $withResponseData
     * @return array
     */
    public static function getBsnApiPrefills(Fund $fund, string $bsn, bool $withResponseData = false): array
    {
        return (new static())->getPrefills($fund, $bsn, $withResponseData);
    }

    /**
     * @param Fund $fund
     * @param string $bsn
     * @param bool $withResponseData
     * @return array
     */
    public function getPrefills(Fund $fund, string $bsn, bool $withResponseData = false): array
    {
        $person = IConnect::make($fund->organization->getIConnectApiConfigs())->getPerson($bsn, [
            'parents', 'children', 'partners',
        ]);

        // return error if API request failed or data by BSN not found
        if (!$person?->response()?->success()) {
            if ($person?->response()->getCode() === 404) {
                return [
                    'error' => [
                        'key' => static::PREFILL_ERROR_NOT_FOUND,
                        'message' => trans('person_bsn_api.errors.not_found'),
                    ],
                    'response' => $withResponseData ? $person?->getResponseData() : null,
                ];
            }

            return [
                'error' => [
                    'key' => static::PREFILL_ERROR_CONNECTION_ERROR,
                    'message' => trans('person_bsn_api.errors.connection_error'),
                ],
                'response' => $withResponseData ? $person?->getResponseData() : null,
            ];
        }

        // prepare person prefills and check if all required criteria filled
        $personPrefills = $this->getPersonPrefills($fund, $person);

        if ($this->isRequiredCriteriaFilledWithPrefills($fund, $personPrefills)) {
            return [
                'error' => [
                    'key' => static::PREFILL_ERROR_NOT_FILLED_REQUIRED_CRITERIA,
                    'message' => trans('person_bsn_api.errors.not_filled_required_criteria'),
                ],
                'response' => $withResponseData ? $person->getResponseData() : null,
            ];
        }

        // prepare partner prefills and return error if voucher already was taken by partner
        try {
            $partner = $this->getPartnerPrefills($fund, $person);
        } catch (PersonBsnApiIsTakenByPartnerException) {
            return [
                'error' => [
                    'key' => static::PREFILL_ERROR_TAKEN_BY_PARTNER,
                    'message' => trans('person_bsn_api.errors.taken_by_partner'),
                ],
                'response' => $withResponseData ? $person->getResponseData() : null,
            ];
        }

        // prepare children prefills with group counts by age
        $childrenPrefills = $this->getChildrenPrefillsWithGroupCounts($fund, $person);

        if (Arr::has($childrenPrefills, 'children_groups_counts')) {
            $childrenPrefills = $this->addPartnersGenderFemaleCountToChildrenGroup(
                $person,
                $partner,
                $childrenPrefills
            );
        }

        $this->checkMissedFields($person, 'person');
        $this->checkMissedPersonApiFields($personPrefills);

        return [
            'error' => null,
            'missed_fields' => [
                'info' => $this->infoMissedFields,
                'warning' => $this->warningMissedFields,
            ],
            'response' => $withResponseData ? $person->getResponseData() : null,
            'person' => [
                ...$personPrefills,
                [
                    'record_type_key' => $fund::RECORD_TYPE_KEY_CHILDREN_SAME_ADDRESS,
                    'value' => Arr::get($childrenPrefills, 'children_count', 0),
                ], [
                    'record_type_key' => $fund::RECORD_TYPE_KEY_PARTNERS_SAME_ADDRESS,
                    'value' => count($partner) ? 2 : 1,
                ],
            ],
            ...Arr::only($childrenPrefills, ['children', 'children_groups_counts']),
            ...compact('partner'),
        ];
    }

    /**
     * @param Fund $fund
     * @param Person $person
     * @return array
     */
    public function getPersonPrefills(Fund $fund, Person $person): array
    {
        $bsnRecordTypes = PersonBsnApiRecordType::query()
            ->whereIn('record_type_key', $fund->criteria()->select('record_type_key'))
            ->get();

        $data = $person->getData();

        return $bsnRecordTypes->map(function (PersonBsnApiRecordType $bsnRecordType) use ($data, $person) {
            $rawValue = Arr::get($data, $bsnRecordType->person_bsn_api_field);

            return [
                'record_type_key' => $bsnRecordType->record_type_key,
                'value' => $bsnRecordType
                    ->parsePersonValue(
                        is_numeric($rawValue) || is_string($rawValue) ? $rawValue : '',
                        $bsnRecordType->record_type->control_type,
                    ),
            ];
        })->toArray();
    }

    /**
     * @param Fund $fund
     * @param array $personPrefills
     * @return bool
     */
    protected function isRequiredCriteriaFilledWithPrefills(Fund $fund, array $personPrefills): bool
    {
        return $fund->criteria
            ->where('fill_type', FundCriterion::FILL_TYPE_PREFILL)
            ->where('optional', false)
            ->filter(function (FundCriterion $criterion) use ($personPrefills) {
                $prefill = Arr::first($personPrefills, fn (array $item) => $item['record_type_key'] === $criterion->record_type_key);

                return is_null(Arr::get($prefill, 'value')) || Arr::get($prefill, 'value') === '';
            })
            ->isNotEmpty();
    }

    /**
     * @param Fund $fund
     * @param Person $person
     * @throws PersonBsnApiIsTakenByPartnerException
     * @return array
     */
    protected function getPartnerPrefills(Fund $fund, Person $person): array
    {
        $partnerRequired = $fund->criteria
            ->where('fill_type', FundCriterion::FILL_TYPE_PREFILL)
            ->whereIn('record_type_key', [
                $fund::RECORD_TYPE_KEY_PARTNERS_SAME_ADDRESS,
                $fund::RECORD_TYPE_KEY_CHILDREN_12_17_PARTNERS_SAME_ADDRESS_GENDER_FEMALE,
            ])
            ->isNotEmpty();

        if ($partnerRequired) {
            $partnerApi = $this->getPartnerFromBsnApi($fund, $person);

            // check if taken by partner
            if ($partnerApi) {
                $identity = Identity::findByBsn($person->getBSN());
                $partner = Identity::findByBsn($partnerApi->getBSN());

                if (($partner && $fund->identityHasActiveVoucher($partner)) || ($identity && $fund->isTakenByPartner($identity))) {
                    throw new PersonBsnApiIsTakenByPartnerException();
                }

                $this->checkMissedFields($partnerApi, 'partner');

                return [[
                    'record_type_key' => 'partner_bsn',
                    'value' => $partnerApi->getBSN(),
                ], [
                    'record_type_key' => 'partner_first_name',
                    'value' => $partnerApi->getFirstName(),
                ], [
                    'record_type_key' => 'partner_last_name',
                    'value' => $partnerApi->getLastName(),
                ], [
                    'record_type_key' => 'partner_birth_date',
                    'value' => $partnerApi->getBirthDate(),
                ], [
                    'record_type_key' => 'partner_gender',
                    'value' => $partnerApi->getGender(),
                ]];
            }
        }

        return [];
    }

    /**
     * @param Fund $fund
     * @param Person $person
     * @return array
     */
    protected function getChildrenPrefillsWithGroupCounts(Fund $fund, Person $person): array
    {
        $childrenCount = 0;

        $childrenRequired = $fund->criteria
            ->where('fill_type', FundCriterion::FILL_TYPE_PREFILL)
            ->whereIn('record_type_key', [
                $fund::RECORD_TYPE_KEY_CHILDREN_SAME_ADDRESS,
                $fund::RECORD_TYPE_KEY_CHILDREN_12_17_PARTNERS_SAME_ADDRESS_GENDER_FEMALE,
            ])
            ->isNotEmpty();

        $configs = Config::get("forus.children_age_groups.groups.{$fund->fund_config->key}");
        $configBase = Config::get("forus.children_age_groups.base.{$fund->fund_config->key}");

        $baseMinAge = Arr::get($configBase, 'from', 0);
        $baseMaxAge = Arr::get($configBase, 'to', 99);

        if ($childrenRequired && $configs) {
            $children = [];

            $groups = array_reduce($configs, fn (array $acc, array $config) => [
                ...$acc, ...[Arr::get($config, 'record_type_key') => 0],
            ], []);

            $childrenApi = $this->getChildrenFromBsnApi($fund, $person);

            /**
             * @var int $index
             * @var Person $personChild
             */
            foreach ($childrenApi as $index => $personChild) {
                $i = $index + 1;

                $children[] = [[
                    'record_type_key' => "child_{$i}_bsn",
                    'value' => $personChild->getBSN(),
                ], [
                    'record_type_key' => "child_{$i}_first_name",
                    'value' => $personChild->getFirstName(),
                ], [
                    'record_type_key' => "child_{$i}_last_name",
                    'value' => $personChild->getLastName(),
                ], [
                    'record_type_key' => "child_{$i}_birth_date",
                    'value' => $personChild->getBirthDate(),
                ], [
                    'record_type_key' => "child_{$i}_gender",
                    'value' => $personChild->getGender(),
                ]];

                $this->checkMissedFields($personChild, "child_$i");

                foreach ($configs as $config) {
                    $recordKey = Arr::get($config, 'record_type_key');
                    $minAge = Arr::get($config, 'from');
                    $maxAge = Arr::get($config, 'to');
                    $gender = Arr::get($config, 'gender');

                    if (
                        (int) $personChild->getAge() >= $minAge &&
                        (int) $personChild->getAge() <= $maxAge &&
                        (!$gender || $gender === $personChild->getGender())
                    ) {
                        $groups[$recordKey] = $groups[$recordKey] + 1;
                    }
                }

                if (
                    (int) $personChild->getAge() >= $baseMinAge &&
                    (int) $personChild->getAge() <= $baseMaxAge
                ) {
                    $childrenCount++;
                }
            }

            return [
                'children' => $children,
                'children_count' => $childrenCount,
                'children_groups_counts' => array_map(
                    fn ($record_type_key, $value) => compact('record_type_key', 'value'),
                    array_keys($groups),
                    $groups
                ),
            ];
        }

        return [];
    }

    /**
     * @param Person $person
     * @param array $partner
     * @param array $childrenPrefills
     * @return array
     */
    protected function addPartnersGenderFemaleCountToChildrenGroup(
        Person $person,
        array $partner,
        array $childrenPrefills
    ): array {
        $partnerGender = Arr::get(Arr::first(
            $partner,
            fn (array $item) => Arr::get($item, 'record_type_key') === 'partner_gender',
            []
        ), 'value');

        $partnersGenderFemale =
            ($person->getGender() === static::GENDER_FEMALE ? 1 : 0) +
            ($partnerGender === static::GENDER_FEMALE ? 1 : 0);

        $childrenPrefills['children_groups_counts'] = array_map(
            function (array $group) use ($partnersGenderFemale) {
                if ($group['record_type_key'] === Fund::RECORD_TYPE_KEY_CHILDREN_12_17_PARTNERS_SAME_ADDRESS_GENDER_FEMALE) {
                    $group['value'] += $partnersGenderFemale;
                }

                return $group;
            },
            $childrenPrefills['children_groups_counts']
        );

        return $childrenPrefills;
    }

    /**
     * @param Fund $fund
     * @param Person $person
     * @return Person|null
     */
    protected function getPartnerFromBsnApi(Fund $fund, Person $person): ?Person
    {
        $bsnService = IConnect::make($fund->organization->getIConnectApiConfigs());
        $partner = $person->getRelated('partners')[0] ?? null;
        $address = $person->getAddress();

        if ($partner) {
            if (!($bsn = $partner->getBSN())) {
                $this->addWarningMissedFields('partner', 'bsn');

                return null;
            }

            $personPartner = $bsnService->getPerson($bsn);

            if (!$personPartner?->response()?->success()) {
                return null;
            }

            $this->checkMissedFields($personPartner, 'partner', 'address');

            return $address === $personPartner->getAddress() ? $personPartner : null;
        }

        return null;
    }

    /**
     * @param Fund $fund
     * @param Person $person
     * @return array
     */
    protected function getChildrenFromBsnApi(Fund $fund, Person $person): array
    {
        $children = [];
        $bsnService = IConnect::make($fund->organization->getIConnectApiConfigs());
        $address = $person->getAddress();

        foreach ($person->getRelated('children') as $child) {
            if ($bsn = $child->getBSN()) {
                $personChild = $bsnService->getPerson($bsn);

                if (!$personChild?->response()?->success()) {
                    continue;
                }

                $this->checkMissedFields($personChild, 'children', 'address');

                if ($address === $personChild->getAddress()) {
                    $children[] = $personChild;
                }
            } else {
                $this->addWarningMissedFields('children', 'bsn');
            }
        }

        return $children;
    }

    /**
     * @param Person $person
     * @param string $key
     * @param string|null $onlyKey
     * @return void
     */
    protected function checkMissedFields(
        Person $person,
        string $key,
        ?string $onlyKey = null
    ): void {
        $infoMissedFields = [];
        $warningMissedFields = [];

        if ($onlyKey) {
            $value = match ($onlyKey) {
                'bsn' => $person->getBSN(),
                'address' => $person->getAddress(),
            };

            $this->checkMissedField($value, $onlyKey, $warningMissedFields);
        } else {
            $this->checkMissedField($person->getGender(), 'gender', $warningMissedFields);
            $this->checkMissedField($person->getBirthDate(), 'birth_date', $warningMissedFields);

            $this->checkMissedField($person->getFirstName(), 'first_name', $infoMissedFields);
            $this->checkMissedField($person->getLastName(), 'last_name', $infoMissedFields);
        }

        if (count($warningMissedFields)) {
            $this->addWarningMissedFields($key, $warningMissedFields);
        }

        if (count($infoMissedFields)) {
            $this->addInfoMissedFields($key, $infoMissedFields);
        }
    }

    /**
     * @param array $fields
     * @return void
     */
    protected function checkMissedPersonApiFields(array $fields): void
    {
        $key = 'person';
        $infoMissedFields = [];
        $warningMissedFields = [];

        $trackInfoFields = [
            'street',
            'house_number',
        ];

        $trackWarningInfoFields = [
            'postal_code',
        ];

        $infoFields = array_filter($fields, fn ($field) => in_array($field['record_type_key'], $trackInfoFields));

        foreach ($infoFields as $field) {
            $this->checkMissedField($field['value'], $field['record_type_key'], $infoMissedFields);
        }

        $warningFields = array_filter($fields, fn ($field) => in_array($field['record_type_key'], $trackWarningInfoFields));

        foreach ($warningFields as $field) {
            $this->checkMissedField($field['value'], $field['record_type_key'], $warningMissedFields);
        }

        if (count($infoMissedFields)) {
            $this->addInfoMissedFields($key, $infoMissedFields);
        }

        if (count($warningMissedFields)) {
            $this->addWarningMissedFields($key, $warningMissedFields);
        }
    }

    /**
     * @param string $key
     * @param string|array $values
     * @return void
     */
    protected function addWarningMissedFields(string $key, string|array $values): void
    {
        $this->warningMissedFields[$key] = [
            ...Arr::get($this->warningMissedFields, $key, []),
            ...(array) $values,
        ];
    }

    /**
     * @param string $key
     * @param string|array $values
     * @return void
     */
    protected function addInfoMissedFields(string $key, string|array $values): void
    {
        $this->infoMissedFields[$key] = [
            ...Arr::get($this->infoMissedFields, $key, []),
            ...(array) $values,
        ];
    }

    /**
     * @param string|null $value
     * @param string $key
     * @param array $missedFields
     * @return void
     */
    protected function checkMissedField(?string $value, string $key, array &$missedFields): void
    {
        if (empty(trim($value ?? ''))) {
            $missedFields[] = $key;
        }
    }
}
