<?php

namespace Feature;

use App\Models\Fund;
use App\Models\Identity;
use App\Models\Organization;
use App\Models\PhysicalCardType;
use App\Models\RecordType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;
use Tests\Traits\MakesApiRequests;
use Tests\Traits\MakesProductReservations;
use Tests\Traits\MakesTestFundRequests;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;

class PhysicalCardsTest extends TestCase
{
    use MakesTestFunds;
    use MakesApiRequests;
    use DatabaseTransactions;
    use MakesTestFundRequests;
    use MakesTestOrganizations;
    use MakesProductReservations;

    /**
     * Test physical card types basic CRUD operations.
     *
     * @return void
     */
    public function testPhysicalCardTypesBasicCrud(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $data = $this->makePhysicalCardRequestData();

        $organization->forceFill([
            'allow_physical_cards' => true,
        ])->save();

        // assert no types
        $this->apiGetPhysicalCardTypesRequest($organization, $organization->identity)
            ->assertSuccessful()
            ->assertJsonCount(0, 'data');

        // assert type created
        $this->apiMakePhysicalCardTypeRequest($organization, $organization->identity, $data)
            ->assertSuccessful()
            ->assertJsonPath('data.name', $data['name'])
            ->assertJsonPath('data.description', $data['description'])
            ->assertJsonPath('data.code_blocks', $data['code_blocks'])
            ->assertJsonPath('data.code_block_size', $data['code_block_size']);

        // assert type visible
        $this->apiGetPhysicalCardTypesRequest($organization, $organization->identity)
            ->assertSuccessful()
            ->assertJsonCount(1, 'data');

        $this->apiGetPhysicalCardTypeRequest($organization, $organization->physical_card_types[0], $organization->identity)
            ->assertSuccessful()
            ->assertJsonPath('data.id', $organization->physical_card_types[0]->id);

        $data['name'] = 'Updated name';

        // assert type updated
        $this->apiUpdatePhysicalCardTypeRequest($organization, $organization->physical_card_types[0], $organization->identity, $data)
            ->assertSuccessful()
            ->assertJsonPath('data.name', 'Updated name');

        // assert type deleted
        $this->apiDeletePhysicalCardTypeRequest($organization, $organization->physical_card_types[0], $organization->identity)
            ->assertSuccessful();
    }

    /**
     * Test getting a list of physical card types.
     *
     * @return void
     */
    public function testPhysicalCardTypesGetList(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $organization->forceFill([
            'allow_physical_cards' => true,
        ])->save();

        // assert no types
        $this->apiGetPhysicalCardTypesRequest($organization, $organization->identity)
            ->assertSuccessful()
            ->assertJsonCount(0, 'data');

        // assert types created
        $this->apiMakePhysicalCardTypeRequest($organization, $organization->identity, $this->makePhysicalCardRequestData());
        $this->apiMakePhysicalCardTypeRequest($organization, $organization->identity, $this->makePhysicalCardRequestData());
        $this->apiMakePhysicalCardTypeRequest($organization, $organization->identity, $this->makePhysicalCardRequestData());
        $this->apiMakePhysicalCardTypeRequest($organization, $organization->identity, $this->makePhysicalCardRequestData());
        $this->apiMakePhysicalCardTypeRequest($organization, $organization->identity, $this->makePhysicalCardRequestData());

        // assert types visible
        $this->apiUpdateFundRequest($organization, $fund, $organization->identity, [
            'enable_physical_card_types' => [$organization->physical_card_types[0]->id],
        ]);

        // assert types filtered by fund
        $this->apiGetPhysicalCardTypesRequest($organization, $organization->identity, ['fund_id' => $fund->id])
            ->assertSuccessful()
            ->assertJsonPath('data.0.id', $organization->physical_card_types[0]->id)
            ->assertJsonCount(1, 'data');

        // assert types paginated
        $this->apiGetPhysicalCardTypesRequest($organization, $organization->identity, ['per_page' => 2])
            ->assertSuccessful()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 5);
    }

    /**
     * @return void
     */
    public function testStoreAndUpdatePhysicalCardType(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $data = $this->makePhysicalCardRequestData();

        $organization->forceFill([
            'allow_physical_cards' => true,
        ])->save();

        // assert type created
        $this->apiMakePhysicalCardTypeRequest($organization, $organization->identity, $data)
            ->assertSuccessful()
            ->assertJsonPath('data.name', $data['name'])
            ->assertJsonPath('data.description', $data['description'])
            ->assertJsonPath('data.code_blocks', $data['code_blocks'])
            ->assertJsonPath('data.code_block_size', $data['code_block_size']);

        // assert validation errors
        $this->apiMakePhysicalCardTypeRequest($organization, $organization->identity, [])
            ->assertJsonValidationErrorFor('name')
            ->assertJsonValidationErrorFor('code_blocks')
            ->assertJsonValidationErrorFor('code_block_size');

        $updatedData = [
            'name' => 'Updated name',
            'description' => 'Updated description',
            // code blocks and size cant be updated
            'code_blocks' => 5,
            'code_block_size' => 5,
        ];

        // assert only name and description updated
        $this->apiUpdatePhysicalCardTypeRequest($organization, $organization->physical_card_types[0], $organization->identity, $updatedData)
            ->assertSuccessful()
            ->assertJsonPath('data.name', $updatedData['name'])
            ->assertJsonPath('data.description', $updatedData['description'])
            // code blocks and size cant be updated
            ->assertJsonPath('data.code_blocks', $data['code_blocks'])
            ->assertJsonPath('data.code_block_size', $data['code_block_size']);
    }

    /**
     * @return void
     */
    public function testPhysicalCardTypeDelete(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);
        $data = $this->makePhysicalCardRequestData();

        $organization->forceFill([
            'allow_physical_cards' => true,
        ])->save();

        // assert type created
        $this->apiMakePhysicalCardTypeRequest($organization, $organization->identity, $data)->assertSuccessful();

        // assert enable type in fund
        $this->apiUpdateFundRequest($organization, $fund, $organization->identity, [
            'fund_request_physical_card_enable' => true,
            'enable_physical_card_types' => [$organization->physical_card_types[0]->id],
        ]);

        // assert type can't be deleted when enabled in funds
        $this->apiDeletePhysicalCardTypeRequest($organization, $organization->physical_card_types[0], $organization->identity)
            ->assertForbidden();

        // assert disable type in fund
        $this->apiUpdateFundRequest($organization, $fund, $organization->identity, [
            'disable_physical_card_types' => [$organization->physical_card_types[0]->id],
        ]);

        // assert type can be deleted when disabled in funds
        $this->apiDeletePhysicalCardTypeRequest($organization, $organization->physical_card_types[0], $organization->identity)
            ->assertSuccessful();
    }

    /**
     * @return void
     */
    public function testAddPhysicalCardTypeToFund(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);
        $data = $this->makePhysicalCardRequestData();

        $organization->forceFill([
            'allow_physical_cards' => true,
        ])->save();

        // assert type created
        $this->apiMakePhysicalCardTypeRequest($organization, $organization->identity, $data)->assertSuccessful();

        $this->apiUpdateFundRequest($organization, $fund, $organization->identity, [
            'fund_request_physical_card_enable' => true,
            'enable_physical_card_types' => [$organization->physical_card_types[0]->id],
        ]);

        // assert types filtered by fund
        $this->apiGetPhysicalCardTypesRequest($organization, $organization->identity, ['fund_id' => $fund->id])
            ->assertSuccessful()
            ->assertJsonPath('data.0.id', $organization->physical_card_types[0]->id)
            ->assertJsonCount(1, 'data');

        // assert funds filtered by type
        $this->apiGetFundsRequest($organization, $organization->identity, ['physical_card_type_id' => $organization->physical_card_types[0]->id])
            ->assertSuccessful()
            ->assertJsonPath('data.0.id', $fund->id)
            ->assertJsonCount(1, 'data');

        $this->apiUpdateFundRequest($organization, $fund, $organization->identity, [
            'fund_request_physical_card_enable' => true,
            'disable_physical_card_types' => [$organization->physical_card_types[0]->id],
        ]);

        // assert types filtered by fund
        $this->apiGetPhysicalCardTypesRequest($organization, $organization->identity, ['fund_id' => $fund->id])
            ->assertSuccessful()
            ->assertJsonCount(0, 'data');

        // assert funds filtered by type
        $this->apiGetFundsRequest($organization, $organization->identity, ['physical_card_type_id' => $organization->physical_card_types[0]->id])
            ->assertSuccessful()
            ->assertJsonCount(0, 'data');
    }

    /**
     * @return void
     */
    public function testPhysicalCardsList()
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $data = $this->makePhysicalCardRequestData();

        $fund = $this->makeTestFund($organization);
        $fund2 = $this->makeTestFund($organization);
        $voucher = $this->makeTestVoucher($fund, $this->makeIdentity($this->makeUniqueEmail()), amount: 100);
        $voucher2 = $this->makeTestVoucher($fund2, $this->makeIdentity($this->makeUniqueEmail()), amount: 100);

        $organization->forceFill([
            'allow_physical_cards' => true,
        ])->save();

        // assert no physical cards
        $this->apiGetPhysicalCardsRequest($organization, $organization->identity)
            ->assertSuccessful()
            ->assertJsonCount(0, 'data');

        // assert type created
        $this->apiMakePhysicalCardTypeRequest($organization, $organization->identity, $data);

        // assign physical cards to vouchers
        $voucher->addPhysicalCard('1001111111111111', $organization->physical_card_types[0]);
        $voucher2->addPhysicalCard('1009999999999999', $organization->physical_card_types[0]);

        // assert physical cards created
        $this->apiGetPhysicalCardsRequest($organization, $organization->identity)
            ->assertSuccessful()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $voucher->physical_cards[0]->id)
            ->assertJsonPath('data.1.id', $voucher2->physical_cards[0]->id);

        // assert physical cards filtered by fund
        $this->apiGetPhysicalCardsRequest($organization, $organization->identity, ['fund_id' => $fund->id])
            ->assertSuccessful()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $voucher->physical_cards[0]->id);

        $this->apiGetPhysicalCardsRequest($organization, $organization->identity, ['fund_id' => $fund2->id])
            ->assertSuccessful()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $voucher2->physical_cards[0]->id);
    }

    /**
     * @return void
     */
    public function testPhysicalCardRequestInFundApplication()
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);
        $data = $this->makePhysicalCardRequestData();

        $organization->forceFill([
            'allow_physical_cards' => true,
        ])->save();

        $this->apiMakePhysicalCardTypeRequest($organization, $organization->identity, $data);

        $this->apiUpdateFundRequest($organization, $fund, $organization->identity, [
            'fund_request_physical_card_enable' => true,
            'fund_request_physical_card_type_id' => $organization->physical_card_types[0]->id,
        ])->assertJsonValidationErrorFor('fund_request_physical_card_type_id');

        $this->apiUpdateFundRequest($organization, $fund, $organization->identity, [
            'fund_request_physical_card_enable' => true,
            'enable_physical_card_types' => [$organization->physical_card_types[0]->id],
        ])->assertSuccessful();

        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $identity2 = $this->makeIdentity($this->makeUniqueEmail());
        $recordTypeKey = 'test_date_' . token_generator()->generate(32);

        $fund->criteria->each(fn ($criterion) => $criterion->fund_criterion_rules()->delete());
        $fund->criteria()->forceDelete();

        $this->makeRecordType($fund->organization, RecordType::TYPE_DATE, $recordTypeKey);

        $this->updateCriteriaRequest([
            $this->makeCriterion($recordTypeKey, '01-01-2000', '>=', '01-01-1990', '01-01-2020'),
        ], $fund)->assertSuccessful();

        $fund->refresh();

        $this->makeFundRequest($identity, $fund, [], true)->assertJsonValidationErrorFor('records');

        $this->makeFundRequest($identity, $fund, [$this->makeRequestCriterionValue($fund, $recordTypeKey, '01-01-2010')], false)
            ->assertSuccessful();

        $this->apiUpdateFundRequest($organization, $fund, $organization->identity, [
            'fund_request_physical_card_enable' => true,
            'fund_request_physical_card_type_id' => $organization->physical_card_types[0]->id,
        ])->assertSuccessful();

        $this->makeFundRequest($identity2, $fund, [$this->makeRequestCriterionValue($fund, $recordTypeKey, '01-01-2010')], false)
            ->assertJsonValidationErrorFor('physical_card_request_address')
            ->assertJsonValidationErrorFor('physical_card_request_address.city')
            ->assertJsonValidationErrorFor('physical_card_request_address.street')
            ->assertJsonValidationErrorFor('physical_card_request_address.house_nr')
            ->assertJsonValidationErrorFor('physical_card_request_address.postal_code');

        $this->makeFundRequest(
            $identity2,
            $fund,
            [$this->makeRequestCriterionValue($fund, $recordTypeKey, '01-01-2010')],
            false,
            data: [
                'physical_card_request_address' => [
                    'city' => 'Test city',
                    'street' => 'Test street',
                    'house_nr' => '123',
                    'house_nr_addition' => 'B',
                    'postal_code' => '1234 AB',
                ],
            ]
        )->assertSuccessful();

        $this->assertCount(1, $identity2->fund_requests[0]->physical_card_requests);
        $this->assertSame('Test street', $identity2->fund_requests[0]->physical_card_requests[0]->address);
        $this->assertSame('Test city', $identity2->fund_requests[0]->physical_card_requests[0]->city);
        $this->assertSame('1234 AB', $identity2->fund_requests[0]->physical_card_requests[0]->postcode);
        $this->assertSame('123', $identity2->fund_requests[0]->physical_card_requests[0]->house);
        $this->assertSame('B', $identity2->fund_requests[0]->physical_card_requests[0]->house_addition);

        $this->assertSame(
            $organization->physical_card_types[0]->id,
            $identity2->fund_requests[0]->physical_card_requests[0]->physical_card_type_id,
        );

        $this->assertSame(
            $identity2->fund_requests[0]->id,
            $identity2->fund_requests[0]->physical_card_requests[0]->fund_request_id,
        );
    }

    /**
     * @return array
     */
    protected function makePhysicalCardRequestData(): array
    {
        static $i = 0;

        ++$i;

        return [
            'name' => 'Test type ' . $i,
            'description' => 'Test description ' . $i,
            'code_blocks' => 4,
            'code_block_size' => 4,
        ];
    }

    /**
     * @param Organization $organization
     * @param Identity $identity
     * @param array $data
     * @return TestResponse
     */
    protected function apiGetPhysicalCardTypesRequest(
        Organization $organization,
        Identity $identity,
        array $data = [],
    ): TestResponse {
        return $this->getJson(
            "/api/v1/platform/organizations/$organization->id/physical-card-types?" . http_build_query($data),
            $this->makeApiHeaders($identity),
        );
    }

    /**
     * @param Organization $organization
     * @param Identity $identity
     * @param array $data
     * @return TestResponse
     */
    protected function apiMakePhysicalCardTypeRequest(
        Organization $organization,
        Identity $identity,
        array $data,
    ): TestResponse {
        return $this->postJson(
            "/api/v1/platform/organizations/$organization->id/physical-card-types",
            $data,
            $this->makeApiHeaders($identity),
        );
    }

    /**
     * @param Organization $organization
     * @param PhysicalCardType $type
     * @param Identity $identity
     * @param array $data
     * @return TestResponse
     */
    protected function apiGetPhysicalCardTypeRequest(
        Organization $organization,
        PhysicalCardType $type,
        Identity $identity,
        array $data = [],
    ): TestResponse {
        return $this->getJson(
            "/api/v1/platform/organizations/$organization->id/physical-card-types/$type->id?" . http_build_query($data),
            $this->makeApiHeaders($identity),
        );
    }

    /**
     * @param Organization $organization
     * @param PhysicalCardType $type
     * @param Identity $identity
     * @param array $data
     * @return TestResponse
     */
    protected function apiUpdatePhysicalCardTypeRequest(
        Organization $organization,
        PhysicalCardType $type,
        Identity $identity,
        array $data = [],
    ): TestResponse {
        return $this->patchJson(
            "/api/v1/platform/organizations/$organization->id/physical-card-types/$type->id",
            $data,
            $this->makeApiHeaders($identity),
        );
    }

    /**
     * @param Organization $organization
     * @param PhysicalCardType $type
     * @param Identity $identity
     * @param array $data
     * @return TestResponse
     */
    protected function apiDeletePhysicalCardTypeRequest(
        Organization $organization,
        PhysicalCardType $type,
        Identity $identity,
        array $data = [],
    ): TestResponse {
        return $this->deleteJson(
            "/api/v1/platform/organizations/$organization->id/physical-card-types/$type->id",
            $data,
            $this->makeApiHeaders($identity),
        );
    }

    /**
     * @param Organization $organization
     * @param Identity $identity
     * @param array $data
     * @return TestResponse
     */
    protected function apiGetFundsRequest(
        Organization $organization,
        Identity $identity,
        array $data = [],
    ): TestResponse {
        return $this->getJson(
            "/api/v1/platform/organizations/$organization->id/funds?" . http_build_query($data),
            $this->makeApiHeaders($identity),
        );
    }

    /**
     * @param Organization $organization
     * @param Fund $fund
     * @param Identity $identity
     * @param array $data
     * @return TestResponse
     */
    protected function apiUpdateFundRequest(
        Organization $organization,
        Fund $fund,
        Identity $identity,
        array $data = [],
    ): TestResponse {
        return $this->patchJson(
            "/api/v1/platform/organizations/$organization->id/funds/$fund->id",
            $data,
            $this->makeApiHeaders($identity),
        );
    }

    /**
     * @param Organization $organization
     * @param Identity $identity
     * @param array $data
     * @return TestResponse
     */
    protected function apiGetPhysicalCardsRequest(
        Organization $organization,
        Identity $identity,
        array $data = [],
    ): TestResponse {
        return $this->getJson(
            "/api/v1/platform/organizations/$organization->id/physical-cards?" . http_build_query($data),
            $this->makeApiHeaders($identity),
        );
    }
}
