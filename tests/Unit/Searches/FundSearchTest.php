<?php

namespace Tests\Unit\Searches;

use App\Models\Fund;
use App\Models\FundProvider;
use App\Models\Implementation;
use App\Searches\FundSearch;
use App\Traits\DoesTesting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Tests\Traits\MakesTestPhysicalCardTypes;

class FundSearchTest extends SearchTestCase
{
    use DoesTesting;
    use MakesTestFunds;
    use MakesTestOrganizations;
    use MakesTestPhysicalCardTypes;

    /**
     * @return void
     */
    public function testQueryBuilds(): void
    {
        $search = new FundSearch([], Fund::query());

        $this->assertQueryBuilds($search->query());
    }

    /**
     * @return void
     */
    public function testFiltersByQuery(): void
    {
        $orgNamePart1 = 'fundsearchorganizationone';
        $orgNamePart2 = 'fundsearchorganizationtwo';

        $namePart1 = 'fundsearchnameone';
        $namePart2 = 'fundsearchnametwo';

        $descriptionTextPart1 = 'fundsearchdescriptionone';
        $descriptionTextPart2 = 'fundsearchdescriptiontwo';

        $descriptionShortPart1 = 'fundsearchshortone';
        $descriptionShortPart2 = 'fundsearchshorttwo';

        $organization1 = $this->makeTestOrganization($this->makeIdentity(), ['name' => "Organization $orgNamePart1"]);
        $organization2 = $this->makeTestOrganization($this->makeIdentity(), ['name' => "Organization $orgNamePart2"]);

        $fund1 = $this->makeTestFund($organization1, [
            'name' => "Fund $namePart1 name",
            'description_text' => "Fund $descriptionTextPart1 description text",
            'description_short' => "Fund $descriptionShortPart1 description short",
        ]);

        $fund2 = $this->makeTestFund($organization2, [
            'name' => "Fund $namePart2 name",
            'description_text' => "Fund $descriptionTextPart2 description text",
            'description_short' => "Fund $descriptionShortPart2 description short",
        ]);

        // assert by organization name
        $this->assertSearchIds(['q' => $orgNamePart1], [$fund1->id]);
        $this->assertSearchIds(['q' => $orgNamePart2], [$fund2->id]);

        // assert by fund name
        $this->assertSearchIds(['q' => $namePart1], [$fund1->id]);
        $this->assertSearchIds(['q' => $namePart2], [$fund2->id]);

        // assert by description_text
        $this->assertSearchIds(['q' => $descriptionTextPart1], [$fund1->id]);
        $this->assertSearchIds(['q' => $descriptionTextPart2], [$fund2->id]);

        // assert by description_short
        $this->assertSearchIds(['q' => $descriptionShortPart1], [$fund1->id]);
        $this->assertSearchIds(['q' => $descriptionShortPart2], [$fund2->id]);
    }

    /**
     * @return void
     */
    public function testFiltersByOrganizationId(): void
    {
        $organization1 = $this->makeTestOrganization($this->makeIdentity());
        $organization2 = $this->makeTestOrganization($this->makeIdentity());

        $fund1 = $this->makeTestFund($organization1);
        $fund2 = $this->makeTestFund($organization2);

        $this->assertSearchIds(['organization_id' => $organization1->id], [$fund1->id]);
        $this->assertSearchIds(['organization_id' => $organization2->id], [$fund2->id]);

        $this->assertSearchIds(['organization_ids' => [$organization1->id]], [$fund1->id]);
        $this->assertSearchIds(['organization_ids' => [$organization2->id]], [$fund2->id]);
        $this->assertSearchIds(['organization_ids' => []], []);
    }

    /**
     * @return void
     */
    public function testFiltersByOrganizationScope(): void
    {
        $ownOrganization = $this->makeTestOrganization($this->makeIdentity());
        $partnerOrganization = $this->makeTestOrganization($this->makeIdentity());
        $implementation = $this->makeTestImplementation($ownOrganization);

        $ownFund = $this->makeTestFund($ownOrganization, implementation: $implementation);
        $partnerFund = $this->makeTestFund($partnerOrganization, implementation: $implementation);
        $previousClientKey = request()->headers->get('Client-Key');

        request()->headers->set('Client-Key', $implementation->key);
        Implementation::clearMemo();

        try {
            $this->assertSearchIds([
                'implementation_id' => $implementation->id,
                'organization_scope' => 'own',
            ], [$ownFund->id]);

            $this->assertSearchIds([
                'implementation_id' => $implementation->id,
                'organization_scope' => 'partners',
            ], [$partnerFund->id]);

            $this->assertSearchIds([
                'implementation_id' => $implementation->id,
                'organization_scope' => 'partners',
                'organization_ids' => [$ownOrganization->id],
            ], []);

            $this->assertSearchIds([
                'implementation_id' => $implementation->id,
                'organization_scope' => 'partners',
                'organization_ids' => [$partnerOrganization->id],
            ], [$partnerFund->id]);
        } finally {
            if ($previousClientKey) {
                request()->headers->set('Client-Key', $previousClientKey);
            } else {
                request()->headers->remove('Client-Key');
            }

            Implementation::clearMemo();
        }
    }

    /**
     * @return void
     */
    public function testFiltersByFundId(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());

        $fund1 = $this->makeTestFund($organization);
        $fund2 = $this->makeTestFund($organization);

        $this->assertSearchIds(['fund_id' => $fund1->id], [$fund1->id]);
        $this->assertSearchIds(['fund_id' => $fund2->id], [$fund2->id]);

        $this->assertSearchIds(['fund_ids' => [$fund1->id]], [$fund1->id]);
        $this->assertSearchIds(['fund_ids' => [$fund2->id]], [$fund2->id]);
        $this->assertSearchIds(['fund_ids' => [$fund1->id, $fund2->id]], [$fund1->id, $fund2->id]);
    }

    /**
     * @return void
     */
    public function testFiltersByImplementationId(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());

        $implementation1 = $this->makeTestImplementation($organization);
        $implementation2 = $this->makeTestImplementation($organization);

        $fund1 = $this->makeTestFund($organization, implementation: $implementation1);
        $fund2 = $this->makeTestFund($organization, implementation: $implementation2);

        $this->assertSearchIds(['implementation_id' => $implementation1->id], [$fund1->id]);
        $this->assertSearchIds(['implementation_id' => $implementation2->id], [$fund2->id]);
    }

    /**
     * @return void
     */
    public function testFiltersByArchived(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());

        $fund1 = $this->makeTestFund($organization, ['archived' => true]);
        $fund2 = $this->makeTestFund($organization);

        $this->assertSearchIds([
            'organization_id' => $organization->id,
            'with_archived' => false,
        ], [$fund2->id]);

        $this->assertSearchIds([
            'organization_id' => $organization->id,
            'with_archived' => true,
        ], [$fund1->id, $fund2->id]);
    }

    /**
     * @return void
     */
    public function testFiltersByExternal(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());

        $fund1 = $this->makeTestFund($organization, ['external' => true]);
        $fund2 = $this->makeTestFund($organization);

        $this->assertSearchIds([
            'organization_id' => $organization->id,
            'with_external' => false,
        ], [$fund2->id]);

        $this->assertSearchIds([
            'organization_id' => $organization->id,
            'with_external' => true,
        ], [$fund1->id, $fund2->id]);
    }

    /**
     * @return void
     */
    public function testFiltersByConfigured(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());

        $fund1 = $this->makeTestFund($organization, fundConfigsData: ['is_configured' => false]);
        $fund2 = $this->makeTestFund($organization);

        $this->assertSearchIds([
            'organization_id' => $organization->id,
        ], [$fund1->id, $fund2->id]);

        $this->assertSearchIds([
            'organization_id' => $organization->id,
            'configured' => true,
        ], [$fund2->id]);
    }

    /**
     * @return void
     */
    public function testFiltersByPhysicalCardTypeId(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());

        $type1 = $this->makeTestPhysicalCardType($organization);
        $type2 = $this->makeTestPhysicalCardType($organization);

        $fund1 = $this->makeTestFund($organization);
        $fund2 = $this->makeTestFund($organization);

        $fund1->fund_physical_card_types()->create([
            'physical_card_type_id' => $type1->id,
            'allow_physical_card_linking' => true,
            'allow_physical_card_requests' => true,
            'allow_physical_card_deactivation' => true,
        ]);

        $fund2->fund_physical_card_types()->create([
            'physical_card_type_id' => $type2->id,
            'allow_physical_card_linking' => true,
            'allow_physical_card_requests' => true,
            'allow_physical_card_deactivation' => true,
        ]);

        $this->assertSearchIds(['physical_card_type_id' => $type1->id], [$fund1->id]);
        $this->assertSearchIds(['physical_card_type_id' => $type2->id], [$fund2->id]);
    }

    /**
     * @return void
     */
    public function testFiltersByWebshopTag(): void
    {
        $tagPart1 = 'match_webshop';
        $tagPart2 = 'other_webshop';

        $organization = $this->makeTestOrganization($this->makeIdentity());

        $fund1 = $this->makeTestFund($organization);
        $fund2 = $this->makeTestFund($organization);

        $tag1 = $this->makeAndAppendTestFundTag($fund1, "{$tagPart1}_tag");
        $tag2 = $this->makeAndAppendTestFundTag($fund2, "{$tagPart2}_tag");

        $this->assertSearchIds(['tag_id' => $tag1->id], [$fund1->id]);
        $this->assertSearchIds(['tag_id' => $tag2->id], [$fund2->id]);

        $this->assertSearchIds(['tag_ids' => [$tag1->id]], [$fund1->id]);
        $this->assertSearchIds(['tag_ids' => [$tag2->id]], [$fund2->id]);
    }

    /**
     * @return void
     */
    public function testFiltersByProviderTag(): void
    {
        $tagPart1 = 'match_webshop';
        $tagPart2 = 'other_webshop';

        $organization = $this->makeTestOrganization($this->makeIdentity());

        $fund1 = $this->makeTestFund($organization);
        $fund2 = $this->makeTestFund($organization);

        $tag1 = $fund1->tags()->firstOrCreate([
            'key' => Str::slug("{$tagPart1}_tag"),
            'scope' => 'provider',
        ]);

        $tag1->translateOrNew(app()->getLocale())->fill([
            'name' => "{$tagPart1}_tag",
        ])->save();

        $tag2 = $fund2->tags()->firstOrCreate([
            'key' => Str::slug("{$tagPart2}_tag"),
            'scope' => 'provider',
        ]);

        $tag2->translateOrNew(app()->getLocale())->fill([
            'name' => "{$tagPart2}_tag",
        ])->save();

        $this->assertSearchIds(['tag' => $tag1->key], [$fund1->id]);
        $this->assertSearchIds(['tag' => $tag2->key], [$fund2->id]);
    }

    /**
     * @return void
     */
    public function testFiltersByState(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());

        $fund1 = $this->makeTestFund($organization);
        $fund2 = $this->makeTestFund($organization);

        $this->assertSearchIds([
            'organization_id' => $organization->id,
            'state' => $fund1::STATE_ACTIVE,
        ], [$fund1->id, $fund2->id]);

        // make first fund state as closed and assert filters
        $fund1->update(['state' => $fund2::STATE_CLOSED]);

        $this->assertSearchIds([
            'organization_id' => $organization->id,
            'state' => $fund1::STATE_CLOSED,
        ], [$fund1->id]);

        $this->assertSearchIds([
            'organization_id' => $organization->id,
            'state' => $fund1::STATE_ACTIVE,
        ], [$fund2->id]);

        // make first fund state as paused and assert filters
        $fund1->update(['state' => $fund2::STATE_PAUSED]);

        $this->assertSearchIds([
            'organization_id' => $organization->id,
            'state' => $fund1::STATE_CLOSED,
        ], []);

        $this->assertSearchIds([
            'organization_id' => $organization->id,
            'state' => $fund1::STATE_PAUSED,
        ], [$fund1->id]);

        $this->assertSearchIds([
            'organization_id' => $organization->id,
            'state' => $fund1::STATE_ACTIVE,
        ], [$fund2->id]);

        // make first fund state as waiting and assert filters
        $fund1->update(['state' => $fund2::STATE_WAITING]);

        $this->assertSearchIds([
            'organization_id' => $organization->id,
            'state' => $fund1::STATE_CLOSED,
        ], []);

        $this->assertSearchIds([
            'organization_id' => $organization->id,
            'state' => $fund1::STATE_WAITING,
        ], [$fund1->id]);

        $this->assertSearchIds([
            'organization_id' => $organization->id,
            'state' => $fund1::STATE_ACTIVE,
        ], [$fund2->id]);

        // assert filter by state as array
        $this->assertSearchIds([
            'organization_id' => $organization->id,
            'state' => [$fund1::STATE_ACTIVE, $fund1::STATE_WAITING],
        ], [$fund1->id, $fund2->id]);
    }

    /**
     * @param string $filter
     * @return void
     */
    public function testFiltersByHasProducts(string $filter = 'has_products'): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());

        $fund1 = $this->makeTestFund($organization);
        $fund2 = $this->makeTestFund($organization);

        $this->assertSearchIds([
            'organization_id' => $organization->id,
            $filter => false,
        ], [$fund1->id, $fund2->id]);

        // create provider and accepted fund provider for fund1
        // and assert fund1 is visible when $filter = true
        $provider = $this->makeTestProviderOrganization($this->makeIdentity());
        $fundProvider1 = $this->makeTestFundProvider($provider, $fund1);

        $this->assertSearchIds([
            'organization_id' => $organization->id,
            $filter => false,
        ], [$fund2->id]);

        $this->assertSearchIds([
            'organization_id' => $organization->id,
            $filter => true,
        ], [$fund1->id]);

        // mark fund provider as rejected and assert fund1 is not visible when $filter = true
        $fundProvider1->update(['state' => FundProvider::STATE_REJECTED]);

        $this->assertSearchIds([
            'organization_id' => $organization->id,
            $filter => false,
        ], [$fund1->id, $fund2->id]);

        $this->assertSearchIds([
            'organization_id' => $organization->id,
            $filter => true,
        ], []);

        // mark fund provider as accepted but allow types is false
        // and assert fund1 is not visible when $filter = true
        $fundProvider1->update([
            'state' => FundProvider::STATE_ACCEPTED,
            'allow_budget' => false,
            'allow_products' => false,
        ]);

        $this->assertSearchIds([
            'organization_id' => $organization->id,
            $filter => false,
        ], [$fund1->id, $fund2->id]);

        $this->assertSearchIds([
            'organization_id' => $organization->id,
            $filter => true,
        ], []);
    }

    /**
     * @return void
     */
    public function testFiltersByHasProviders(): void
    {
        $this->testFiltersByHasProducts('has_providers');
    }

    /**
     * @return void
     */
    public function testOrdersByCreatedAt(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());

        $fundA = $this->makeTestFund($organization);

        Carbon::setTestNow(now()->addDays(5));
        $fundB = $this->makeTestFund($organization);

        $this->assertSearchOrder([
            'organization_id' => $organization->id,
            'order_by' => 'created_at',
            'order_dir' => 'asc',
        ], [$fundA->id, $fundB->id]);

        $this->assertSearchOrder([
            'organization_id' => $organization->id,
            'order_by' => 'created_at',
            'order_dir' => 'desc',
        ], [$fundB->id, $fundA->id]);
    }

    /**
     * @return void
     */
    public function testOrdersByName(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());

        $fundA = $this->makeTestFund($organization, ['name' => 'A fund name']);
        $fundB = $this->makeTestFund($organization, ['name' => 'B fund name']);

        $this->assertSearchOrder([
            'organization_id' => $organization->id,
            'order_by' => 'name',
            'order_dir' => 'asc',
        ], [$fundA->id, $fundB->id]);

        $this->assertSearchOrder([
            'organization_id' => $organization->id,
            'order_by' => 'name',
            'order_dir' => 'desc',
        ], [$fundB->id, $fundA->id]);
    }

    /**
     * @return void
     */
    public function testOrdersByOrganizationName(): void
    {
        $organizationA = $this->makeTestOrganization($this->makeIdentity(), ['name' => 'A organization name']);
        $organizationB = $this->makeTestOrganization($this->makeIdentity(), ['name' => 'B organization name']);

        $fundA = $this->makeTestFund($organizationA);
        $fundB = $this->makeTestFund($organizationB);

        $this->assertSearchOrder([
            'fund_ids' => [$fundA->id, $fundB->id],
            'order_by' => 'organization_name',
            'order_dir' => 'asc',
        ], [$fundA->id, $fundB->id]);

        $this->assertSearchOrder([
            'fund_ids' => [$fundA->id, $fundB->id],
            'order_by' => 'organization_name',
            'order_dir' => 'desc',
        ], [$fundB->id, $fundA->id]);
    }

    /**
     * @return void
     */
    public function testOrdersByStartDate(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());

        $fundA = $this->makeTestFund($organization, ['start_date' => Carbon::now()->subDays(2)]);
        $fundB = $this->makeTestFund($organization, ['start_date' => Carbon::now()]);

        $this->assertSearchOrder([
            'organization_id' => $organization->id,
            'order_by' => 'start_date',
            'order_dir' => 'asc',
        ], [$fundA->id, $fundB->id]);

        $this->assertSearchOrder([
            'organization_id' => $organization->id,
            'order_by' => 'start_date',
            'order_dir' => 'desc',
        ], [$fundB->id, $fundA->id]);
    }

    /**
     * @return void
     */
    public function testOrdersByEndDate(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());

        $fundA = $this->makeTestFund($organization, ['end_date' => Carbon::now()->addDays(2)]);
        $fundB = $this->makeTestFund($organization, ['end_date' => Carbon::now()->addDays(10)]);

        $this->assertSearchOrder([
            'organization_id' => $organization->id,
            'order_by' => 'end_date',
            'order_dir' => 'asc',
        ], [$fundA->id, $fundB->id]);

        $this->assertSearchOrder([
            'organization_id' => $organization->id,
            'order_by' => 'end_date',
            'order_dir' => 'desc',
        ], [$fundB->id, $fundA->id]);
    }

    /**
     * @return void
     */
    public function testOrdersByOrder(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());

        $fundA = $this->makeTestFund($organization);
        $fundA->forceFill(['order' => 1])->save();

        $fundB = $this->makeTestFund($organization);
        $fundB->forceFill(['order' => 2])->save();

        $this->assertSearchOrder([
            'organization_id' => $organization->id,
            'order_by' => 'order',
            'order_dir' => 'asc',
        ], [$fundA->id, $fundB->id]);

        $this->assertSearchOrder([
            'organization_id' => $organization->id,
            'order_by' => 'order',
            'order_dir' => 'desc',
        ], [$fundB->id, $fundA->id]);
    }

    /**
     * @param array $filters
     * @return FundSearch
     */
    private function makeSearch(array $filters): FundSearch
    {
        return new FundSearch($filters, Fund::query());
    }

    /**
     * @param array $filters
     * @param array $expectedIds
     * @return void
     */
    private function assertSearchIds(array $filters, array $expectedIds): void
    {
        $expected = collect($expectedIds)->sort()->values()->toArray();
        $search = $this->makeSearch($filters);
        $actual = collect($search->query()->pluck('id')->toArray())->sort()->values()->toArray();

        $this->assertSame($expected, $actual);
    }

    /**
     * @param array $filters
     * @param array $expectedIds
     * @return void
     */
    private function assertSearchOrder(array $filters, array $expectedIds): void
    {
        $search = $this->makeSearch($filters);
        $actual = $search->query()->pluck('id')->toArray();

        $this->assertSame($expectedIds, $actual);
    }
}
