<?php

namespace Tests\Unit;

use App\Models\Fund;
use App\Models\FundCriterion;
use App\Models\PersonBsnApiRecordType;
use App\Models\RecordType;
use App\Services\IConnectApiService\IConnectPrefill;
use App\Services\IConnectApiService\Objects\Person;
use App\Traits\DoesTesting;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
use Tests\CreatesApplication;
use Tests\TestCase;
use Tests\Traits\MakesTestFundRequests;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;

class IConnectPrefillTest extends TestCase
{
    use DoesTesting;
    use DatabaseTransactions;
    use CreatesApplication;
    use MakesTestFundRequests;
    use MakesTestFunds;
    use MakesTestOrganizations;

    /**
     * @return void
     */
    public function testGetPersonPrefillsMapsControlTypes(): void
    {
        $identity = $this->makeIdentity(email: $this->makeUniqueEmail());
        $organization = $this->makeTestOrganization($identity);
        $fund = $this->makeTestFund($organization);

        $givenNameKey = token_generator()->generate(16);
        $birthDateKey = token_generator()->generate(16);
        $houseNumberKey = token_generator()->generate(16);
        $missingKey = token_generator()->generate(16);

        $recordTypes = [
            $givenNameKey => $this->makeCriteriaRecordType(
                $organization,
                RecordType::TYPE_STRING,
                RecordType::CONTROL_TYPE_TEXT,
                $givenNameKey,
            ),
            $birthDateKey => $this->makeCriteriaRecordType(
                $organization,
                RecordType::TYPE_DATE,
                RecordType::CONTROL_TYPE_DATE,
                $birthDateKey,
            ),
            $houseNumberKey => $this->makeCriteriaRecordType(
                $organization,
                RecordType::TYPE_NUMBER,
                RecordType::CONTROL_TYPE_NUMBER,
                $houseNumberKey,
            ),
            $missingKey => $this->makeCriteriaRecordType(
                $organization,
                RecordType::TYPE_STRING,
                RecordType::CONTROL_TYPE_TEXT,
                $missingKey,
            ),
        ];

        foreach (array_keys($recordTypes) as $recordTypeKey) {
            $fund->criteria()->create([
                'record_type_key' => $recordTypeKey,
                'operator' => '*',
                'value' => '',
                'show_attachment' => false,
                'optional' => true,
                'fill_type' => FundCriterion::FILL_TYPE_PREFILL,
            ]);
        }

        PersonBsnApiRecordType::create([
            'person_bsn_api_field' => 'naam.voornamen',
            'record_type_key' => $givenNameKey,
        ]);
        PersonBsnApiRecordType::create([
            'person_bsn_api_field' => 'geboorte.datum.datum',
            'record_type_key' => $birthDateKey,
        ]);
        PersonBsnApiRecordType::create([
            'person_bsn_api_field' => 'verblijfplaats.huisnummer',
            'record_type_key' => $houseNumberKey,
        ]);
        PersonBsnApiRecordType::create([
            'person_bsn_api_field' => 'naam.onbekend',
            'record_type_key' => $missingKey,
        ]);

        $person = new Person([
            'naam' => [
                'voornamen' => 'Zon',
            ],
            'geboorte' => [
                'datum' => [
                    'datum' => '1970-12-30',
                ],
            ],
            'verblijfplaats' => [
                'huisnummer' => '14',
            ],
        ]);

        $prefills = collect((new IConnectPrefill())->getPersonPrefills($fund, $person))
            ->keyBy('record_type_key');

        $this->assertSame('Zon', $prefills[$givenNameKey]['value']);
        $this->assertSame('30-12-1970', $prefills[$birthDateKey]['value']);
        $this->assertEquals(14.0, $prefills[$houseNumberKey]['value']);
        $this->assertNull($prefills[$missingKey]['value']);
    }

    /**
     * @return void
     */
    public function testGetChildrenPrefillsGroupsCounts(): void
    {
        Config::set('forus.children_age_groups.unit-test', [[
            'record_type_key' => 'children_age_group_0_3',
            'from' => 0,
            'to' => 3,
        ], [
            'record_type_key' => 'children_age_group_4_10',
            'from' => 4,
            'to' => 10,
        ]]);

        $identity = $this->makeIdentity(email: $this->makeUniqueEmail());
        $organization = $this->makeTestOrganization($identity);
        $fund = $this->makeTestFund($organization, [], ['key' => 'unit-test']);

        $fund->criteria()->create([
            'record_type_key' => Fund::RECORD_TYPE_KEY_CHILDREN_SAME_ADDRESS,
            'operator' => '>=',
            'value' => 1,
            'show_attachment' => false,
            'optional' => true,
            'fill_type' => FundCriterion::FILL_TYPE_PREFILL,
        ]);

        $fund->load('criteria', 'fund_config');

        $children = [
            new Person([
                'burgerservicenummer' => '111111111',
                'naam' => ['voornamen' => 'A', 'geslachtsnaam' => 'Alpha'],
                'geboorte' => ['datum' => ['datum' => '2021-01-01']],
                'geslachtsaanduiding' => 'M',
                'leeftijd' => 2,
            ]),
            new Person([
                'burgerservicenummer' => '222222222',
                'naam' => ['voornamen' => 'B', 'geslachtsnaam' => 'Beta'],
                'geboorte' => ['datum' => ['datum' => '2018-01-01']],
                'geslachtsaanduiding' => 'V',
                'leeftijd' => 5,
            ]),
            new Person([
                'burgerservicenummer' => '333333333',
                'naam' => ['voornamen' => 'C', 'geslachtsnaam' => 'Gamma'],
                'geboorte' => ['datum' => ['datum' => '2013-01-01']],
                'geslachtsaanduiding' => 'V',
                'leeftijd' => 10,
            ]),
        ];

        $prefill = new class ($children) extends IConnectPrefill {
            public function __construct(private readonly array $children)
            {
            }

            public function getChildrenPrefillsWithGroupCountsPublic(Fund $fund, Person $person): array
            {
                return $this->getChildrenPrefillsWithGroupCounts($fund, $person);
            }

            protected function getChildrenFromBsnApi(Fund $fund, Person $person): array
            {
                return $this->children;
            }
        };

        $result = $prefill->getChildrenPrefillsWithGroupCountsPublic($fund, new Person([]));
        $groupCounts = collect($result['children_groups_counts'])->keyBy('record_type_key');

        $this->assertSame(1, $groupCounts['children_age_group_0_3']['value']);
        $this->assertSame(2, $groupCounts['children_age_group_4_10']['value']);
        $this->assertCount(3, $result['children']);
        $this->assertSame('child_1_bsn', $result['children'][0][0]['record_type_key']);
    }

    /**
     * @return void
     */
    public function testGetChildrenPrefillsReturnsEmptyWhenNotRequired(): void
    {
        Config::set('forus.children_age_groups.unit-test-not-required', [[
            'record_type_key' => 'children_age_group_0_3',
            'from' => 0,
            'to' => 3,
        ]]);

        $identity = $this->makeIdentity(email: $this->makeUniqueEmail());
        $organization = $this->makeTestOrganization($identity);
        $fund = $this->makeTestFund($organization, [], ['key' => 'unit-test-not-required']);

        $prefill = new class () extends IConnectPrefill {
            public function getChildrenPrefillsWithGroupCountsPublic(Fund $fund, Person $person): array
            {
                return $this->getChildrenPrefillsWithGroupCounts($fund, $person);
            }
        };

        $this->assertSame([], $prefill->getChildrenPrefillsWithGroupCountsPublic($fund, new Person([])));
    }

    /**
     * @return void
     */
    public function testGetChildrenPrefillsReturnsEmptyWhenConfigMissing(): void
    {
        $identity = $this->makeIdentity(email: $this->makeUniqueEmail());
        $organization = $this->makeTestOrganization($identity);
        $fund = $this->makeTestFund($organization, [], ['key' => 'unit-test-missing']);

        $fund->criteria()->create([
            'record_type_key' => Fund::RECORD_TYPE_KEY_CHILDREN_SAME_ADDRESS,
            'operator' => '>=',
            'value' => 1,
            'show_attachment' => false,
            'optional' => true,
            'fill_type' => FundCriterion::FILL_TYPE_PREFILL,
        ]);

        $fund->load('criteria', 'fund_config');

        $prefill = new class () extends IConnectPrefill {
            public function getChildrenPrefillsWithGroupCountsPublic(Fund $fund, Person $person): array
            {
                return $this->getChildrenPrefillsWithGroupCounts($fund, $person);
            }
        };

        $this->assertSame([], $prefill->getChildrenPrefillsWithGroupCountsPublic($fund, new Person([])));
    }
}
