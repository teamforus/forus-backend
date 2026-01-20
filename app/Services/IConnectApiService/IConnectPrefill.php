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

        return [
            'error' => null,
            'response' => $withResponseData ? $person->getResponseData() : null,
            'person' => [
                ...$personPrefills,
                [
                    'record_type_key' => $fund::RECORD_TYPE_KEY_PARTNERS_SAME_ADDRESS,
                    'value' => count($partner) ? 2 : 1,
                ], [
                    'record_type_key' => $fund::RECORD_TYPE_KEY_CHILDREN_SAME_ADDRESS,
                    'value' => count(Arr::get($childrenPrefills, 'children', [])),
                ],
            ],
            ...$childrenPrefills,
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
            ->where('record_type_key', $fund::RECORD_TYPE_KEY_PARTNERS_SAME_ADDRESS)
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
        $childrenRequired = $fund->criteria
            ->where('fill_type', FundCriterion::FILL_TYPE_PREFILL)
            ->where('record_type_key', $fund::RECORD_TYPE_KEY_CHILDREN_SAME_ADDRESS)
            ->isNotEmpty();

        $configs = Config::get("forus.children_age_groups.{$fund->fund_config->key}");

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
            }

            return [
                'children' => $children,
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
     * @param Fund $fund
     * @param Person $person
     * @return Person|null
     */
    protected function getPartnerFromBsnApi(Fund $fund, Person $person): ?Person
    {
        $bsnService = IConnect::make($fund->organization->getIConnectApiConfigs());
        $partner = $person->getRelated('partners')[0] ?? null;
        $address = $person->getAddress();

        if ($partner && ($bsn = $partner->getBSN())) {
            $personPartner = $bsnService->getPerson($bsn);

            return $personPartner?->response()?->success() && $address === $personPartner->getAddress()
                ? $personPartner
                : null;
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

                if ($personChild?->response()?->success() && $address === $personChild->getAddress()) {
                    $children[] = $personChild;
                }
            }
        }

        return $children;
    }
}
