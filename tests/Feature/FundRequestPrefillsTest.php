<?php

namespace Tests\Feature;

use App\Models\Fund;
use App\Models\FundCriterion;
use App\Models\Identity;
use App\Models\RecordType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;
use Tests\Traits\MakesTestFundRequestPrefills;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestIdentities;
use Tests\Traits\MakesTestOrganizations;
use Throwable;

class FundRequestPrefillsTest extends TestCase
{
    use DatabaseTransactions;
    use MakesTestFundRequestPrefills;
    use MakesTestFunds;
    use MakesTestIdentities;
    use MakesTestOrganizations;

    protected function setUp(): void
    {
        parent::setUp();

        Identity::findByBsn('999994542')?->delete();
        Identity::findByBsn('999993112')?->delete();

        Config::set('forus.person_bsn.fund_prefill_cache_time', 0);
        Cache::flush();
    }

    /**
     * @throws Throwable
     */
    public function testFundRequestPrefillsEndpointIncludesPartnerAndChildren(): void
    {
        $this->fakePersonBsnApiResponses();

        // create organization and fund with prefills enabled
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $this->enablePersonBsnApiForOrganization($organization);

        $fund = $this->makeTestFund($organization, [
            'type' => 'budget',
        ], [
            'allow_fund_request_prefill' => true,
            'allow_prevalidations' => false,
            'key' => 'nijmegen-vi',
        ]);

        // create record types and person-field mapping for prefills
        $prefillKey = token_generator()->generate(16);
        $this->makePrefillRecordType($organization, $prefillKey, 'naam.geslachtsnaam');

        $this->makeRecordTypeForKey(
            $organization,
            Fund::RECORD_TYPE_KEY_PARTNERS_SAME_ADDRESS,
            RecordType::TYPE_NUMBER,
            RecordType::CONTROL_TYPE_NUMBER,
        );
        $this->makeRecordTypeForKey(
            $organization,
            Fund::RECORD_TYPE_KEY_CHILDREN_SAME_ADDRESS,
            RecordType::TYPE_NUMBER,
            RecordType::CONTROL_TYPE_NUMBER,
        );

        $criteria = [[
            'title' => 'Prefill last name',
            'value' => 'any',
            'operator' => '*',
            'optional' => true,
            'record_type_key' => $prefillKey,
            'show_attachment' => false,
            'fill_type' => FundCriterion::FILL_TYPE_PREFILL,
        ], [
            'title' => 'Partner count',
            'value' => 1,
            'operator' => '>=',
            'optional' => true,
            'record_type_key' => Fund::RECORD_TYPE_KEY_PARTNERS_SAME_ADDRESS,
            'show_attachment' => false,
            'fill_type' => FundCriterion::FILL_TYPE_PREFILL,
        ], [
            'title' => 'Children count',
            'value' => 1,
            'operator' => '>=',
            'optional' => true,
            'record_type_key' => Fund::RECORD_TYPE_KEY_CHILDREN_SAME_ADDRESS,
            'show_attachment' => false,
            'fill_type' => FundCriterion::FILL_TYPE_PREFILL,
        ]];

        $this->makeFundCriteria($fund, $criteria);

        // request prefills for a BSN with partner and children data
        $identity = $this->makeIdentity($this->makeUniqueEmail(), '999993112');

        $response = $this->getJson(
            "/api/v1/platform/funds/$fund->id/prefills",
            $this->makeApiHeaders($this->makeIdentityProxy($identity)),
        );

        $response->assertSuccessful();
        $this->assertNull($response->json('error'));

        // assert person/partner/children payload values
        $person = collect($response->json('person'));
        $this->assertEquals('Zon', $person->firstWhere('record_type_key', $prefillKey)['value']);

        $partner = $response->json('partner');
        $children = $response->json('children');

        $this->assertNotEmpty($partner);
        $this->assertCount(3, $children);

        // assert children group counts
        $groupCounts = collect($response->json('children_groups_counts'));
        $this->assertEquals(2, (int) $groupCounts->firstWhere('record_type_key', 'children_age_group_4_11')['value']);
        $this->assertEquals(1, (int) $groupCounts->firstWhere('record_type_key', 'children_age_group_12_17_gender_female')['value']);
    }

    /**
     * @throws Throwable
     */
    public function testFundRequestPrefillsRequiredCriteriaMissing(): void
    {
        $this->fakePersonBsnApiResponses();

        // create organization and fund with prefills enabled
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $this->enablePersonBsnApiForOrganization($organization);

        $fund = $this->makeTestFund($organization, [
            'type' => 'budget',
        ], [
            'allow_fund_request_prefill' => true,
            'allow_prevalidations' => false,
        ]);

        // create required prefill mapping to a missing field
        $prefillKey = token_generator()->generate(16);
        $this->makePrefillRecordType($organization, $prefillKey, 'missing.path');

        $criteria = [[
            'title' => 'Required missing prefill',
            'value' => 'any',
            'operator' => '*',
            'optional' => false,
            'record_type_key' => $prefillKey,
            'show_attachment' => false,
            'fill_type' => FundCriterion::FILL_TYPE_PREFILL,
        ]];

        $this->makeFundCriteria($fund, $criteria);

        // assert required prefills error
        $identity = $this->makeIdentity($this->makeUniqueEmail(), '999993112');

        $response = $this->getJson(
            "/api/v1/platform/funds/$fund->id/prefills",
            $this->makeApiHeaders($this->makeIdentityProxy($identity)),
        );

        $response->assertSuccessful();
        // assert required prefill error response
        $this->assertEquals('not_filled_required_criteria', $response->json('error.key'));
    }

    /**
     * @throws Throwable
     */
    public function testFundRequestPrefillsTakenByPartner(): void
    {
        $this->fakePersonBsnApiResponses();

        // create organization and fund with prefills enabled
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $this->enablePersonBsnApiForOrganization($organization);

        $fund = $this->makeTestFund($organization, [
            'type' => 'budget',
        ], [
            'allow_fund_request_prefill' => true,
            'allow_prevalidations' => false,
        ]);

        // create partner-required criterion
        $this->makeRecordTypeForKey(
            $organization,
            Fund::RECORD_TYPE_KEY_PARTNERS_SAME_ADDRESS,
            RecordType::TYPE_NUMBER,
            RecordType::CONTROL_TYPE_NUMBER,
        );

        $criteria = [[
            'title' => 'Partner count',
            'value' => 1,
            'operator' => '>=',
            'optional' => true,
            'record_type_key' => Fund::RECORD_TYPE_KEY_PARTNERS_SAME_ADDRESS,
            'show_attachment' => false,
            'fill_type' => FundCriterion::FILL_TYPE_PREFILL,
        ]];

        $this->makeFundCriteria($fund, $criteria);

        // create active voucher for partner BSN
        $partner = $this->makeIdentity($this->makeUniqueEmail(), '999994542');
        $fund->makeVoucher($partner);

        // request prefills for requester with partner voucher
        $identity = $this->makeIdentity($this->makeUniqueEmail(), '999993112');

        $response = $this->getJson(
            "/api/v1/platform/funds/$fund->id/prefills",
            $this->makeApiHeaders($this->makeIdentityProxy($identity)),
        );

        $response->assertSuccessful();
        // assert taken by partner error response
        $this->assertEquals('taken_by_partner', $response->json('error.key'));
    }

    /**
     * @throws Throwable
     */
    public function testFundRequestPrefillsNotFound(): void
    {
        $this->fakePersonBsnApiResponses([
            '111111111' => [
                'status' => 404,
                'body' => [],
            ],
        ]);

        // create organization and fund with prefills enabled
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $this->enablePersonBsnApiForOrganization($organization);

        $fund = $this->makeTestFund($organization, [
            'type' => 'budget',
        ], [
            'allow_fund_request_prefill' => true,
            'allow_prevalidations' => false,
        ]);

        // request prefills for a missing person
        $identity = $this->makeIdentity($this->makeUniqueEmail(), '111111111');

        $response = $this->getJson(
            "/api/v1/platform/funds/$fund->id/prefills",
            $this->makeApiHeaders($this->makeIdentityProxy($identity)),
        );

        $response->assertSuccessful();
        // assert not found error response
        $this->assertEquals('not_found', $response->json('error.key'));
    }

    /**
     * @throws Throwable
     */
    public function testFundRequestPrefillsConnectionError(): void
    {
        $this->fakePersonBsnApiResponses([
            '222222222' => [
                'status' => 500,
                'body' => [],
            ],
        ]);

        // create organization and fund with prefills enabled
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $this->enablePersonBsnApiForOrganization($organization);

        $fund = $this->makeTestFund($organization, [
            'type' => 'budget',
        ], [
            'allow_fund_request_prefill' => true,
            'allow_prevalidations' => false,
        ]);

        // request prefills with a connection error response
        $identity = $this->makeIdentity($this->makeUniqueEmail(), '222222222');

        $response = $this->getJson(
            "/api/v1/platform/funds/$fund->id/prefills",
            $this->makeApiHeaders($this->makeIdentityProxy($identity)),
        );

        $response->assertSuccessful();
        // assert connection error response
        $this->assertEquals('connection_error', $response->json('error.key'));
    }

    /**
     * @throws Throwable
     */
    public function testFundRequestPrefillValueCannotBeModified(): void
    {
        $this->fakePersonBsnApiResponses();

        // create organization and fund with prefills enabled
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $this->enablePersonBsnApiForOrganization($organization);

        $fund = $this->makeTestFund($organization, [
            'type' => 'budget',
        ], [
            'allow_fund_request_prefill' => true,
            'allow_prevalidations' => false,
        ]);

        // create prefill criterion mapped to last name
        $prefillKey = token_generator()->generate(16);
        $this->makePrefillRecordType($organization, $prefillKey, 'naam.geslachtsnaam');

        $criteria = [[
            'title' => 'Prefill last name',
            'value' => 'any',
            'operator' => '*',
            'optional' => false,
            'record_type_key' => $prefillKey,
            'show_attachment' => false,
            'fill_type' => FundCriterion::FILL_TYPE_PREFILL,
        ]];

        $this->makeFundCriteria($fund, $criteria);

        // submit a mismatched prefill value
        $identity = $this->makeIdentity($this->makeUniqueEmail(), '999993112');
        $records = [
            $this->makeRequestCriterionValue($fund, $prefillKey, 'Wrong'),
        ];

        $response = $this->makeFundRequestWithBsn($identity, $fund, $records, true, '999993112');
        // assert prefill validation rejects modified value
        $response->assertJsonValidationErrors(['records.0.value']);
    }

    /**
     * @throws Throwable
     */
    public function testFundRequestStoresPrefillsInRecords(): void
    {
        $this->fakePersonBsnApiResponses();

        // create organization and fund with prefills enabled
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $this->enablePersonBsnApiForOrganization($organization);

        $fund = $this->makeTestFund($organization, [
            'type' => 'budget',
        ], [
            'allow_fund_request_prefill' => true,
            'allow_prevalidations' => false,
            'key' => 'nijmegen-vi',
        ]);

        // create record types and mapping for prefills
        $prefillKey = token_generator()->generate(16);
        $manualKey = token_generator()->generate(16);

        $this->makePrefillRecordType($organization, $prefillKey, 'naam.geslachtsnaam');
        $this->makeRecordTypeForKey(
            $organization,
            $manualKey,
            RecordType::TYPE_NUMBER,
            RecordType::CONTROL_TYPE_NUMBER,
        );
        $this->makeRecordTypeForKey(
            $organization,
            Fund::RECORD_TYPE_KEY_PARTNERS_SAME_ADDRESS,
            RecordType::TYPE_NUMBER,
            RecordType::CONTROL_TYPE_NUMBER,
        );
        $this->makeRecordTypeForKey(
            $organization,
            Fund::RECORD_TYPE_KEY_CHILDREN_SAME_ADDRESS,
            RecordType::TYPE_NUMBER,
            RecordType::CONTROL_TYPE_NUMBER,
        );

        $criteria = [[
            'title' => 'Prefill last name',
            'value' => 'any',
            'operator' => '*',
            'optional' => true,
            'record_type_key' => $prefillKey,
            'show_attachment' => false,
            'fill_type' => FundCriterion::FILL_TYPE_PREFILL,
        ], [
            'title' => 'Partner count',
            'value' => 1,
            'operator' => '>=',
            'optional' => true,
            'record_type_key' => Fund::RECORD_TYPE_KEY_PARTNERS_SAME_ADDRESS,
            'show_attachment' => false,
            'fill_type' => FundCriterion::FILL_TYPE_PREFILL,
        ], [
            'title' => 'Children count',
            'value' => 1,
            'operator' => '>=',
            'optional' => true,
            'record_type_key' => Fund::RECORD_TYPE_KEY_CHILDREN_SAME_ADDRESS,
            'show_attachment' => false,
            'fill_type' => FundCriterion::FILL_TYPE_PREFILL,
        ], [
            'title' => 'Manual number',
            'value' => 1,
            'operator' => '>=',
            'optional' => false,
            'record_type_key' => $manualKey,
            'show_attachment' => false,
        ]];

        $this->makeFundCriteria($fund, $criteria);

        // create fund request with manual value and assert prefills persisted
        $identity = $this->makeIdentity($this->makeUniqueEmail(), '999993112');
        $records = [
            $this->makeRequestCriterionValue($fund, $prefillKey, 'Zon'),
            $this->makeRequestCriterionValue($fund, Fund::RECORD_TYPE_KEY_PARTNERS_SAME_ADDRESS, 2),
            $this->makeRequestCriterionValue($fund, Fund::RECORD_TYPE_KEY_CHILDREN_SAME_ADDRESS, 3),
            $this->makeRequestCriterionValue($fund, $manualKey, 3),
        ];

        $response = $this->makeFundRequestWithBsn($identity, $fund, $records, false, '999993112');
        $response->assertSuccessful();

        // assert fund request record includes prefills
        $fundRequest = $fund->fund_requests()->find($response->json('data.id'));
        $this->assertNotNull($fundRequest);

        $recordKeys = $fundRequest->records()->pluck('record_type_key')->toArray();

        $this->assertContains($prefillKey, $recordKeys);
        $this->assertContains('partner_bsn', $recordKeys);
        $this->assertContains('child_1_first_name', $recordKeys);
        $this->assertContains('children_age_group_18_99', $recordKeys);
        $this->assertContains('children_age_group_12_17_gender_female', $recordKeys);
    }
}
