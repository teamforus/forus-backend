<?php

namespace Tests\Unit\Searches;

use App\Searches\WebshopGenericSearch;
use App\Traits\DoesTesting;
use Exception;
use Illuminate\Support\Carbon;
use Tests\Traits\MakesProductReservations;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizationOffices;
use Tests\Traits\MakesTestOrganizations;
use Tests\Traits\MakesTestProducts;

class WebshopGenericSearchTest extends SearchTestCase
{
    use DoesTesting;
    use MakesTestFunds;
    use MakesTestProducts;
    use MakesTestOrganizations;
    use MakesProductReservations;
    use MakesTestOrganizationOffices;

    /**
     * @throws Exception
     * @return void
     */
    public function testQueryBuilds(): void
    {
        $search = new WebshopGenericSearch([]);

        $this->assertQueryBuilds($search->query('funds'));
    }

    /**
     * @throws Exception
     * @return void
     */
    public function testFiltersByProductQuery(): void
    {
        $productNamePart1 = 'webshopgenericproductnameone';
        $productNamePart2 = 'webshopgenericproductnametwo';

        $productDescriptionTextPart1 = 'webshopgenericproductdescriptionone';
        $productDescriptionTextPart2 = 'webshopgenericproductdescriptiontwo';

        $categoryNamePart1 = 'webshopgenericproductcategoryone';
        $categoryNamePart2 = 'webshopgenericproductcategorytwo';

        $organizationNamePart1 = 'webshopgenericproductorganizationone';
        $organizationNamePart2 = 'webshopgenericproductorganizationtwo';

        $sponsor = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($sponsor);

        $organization1 = $this->makeTestOrganization($this->makeIdentity(), [
            'name' => "$organizationNamePart1 organization",
        ]);

        $organization2 = $this->makeTestOrganization($this->makeIdentity(), [
            'name' => "$organizationNamePart2 organization",
        ]);

        $category1 = $this->makeProductCategory(name: "$categoryNamePart1 category");
        $category2 = $this->makeProductCategory(name: "$categoryNamePart2 category");

        $product1 = $this->createProductForReservation($organization1, [$fund]);
        $product2 = $this->createProductForReservation($organization2, [$fund]);

        $product1->update([
            'name' => "$productNamePart1 product name",
            'description_text' => "$productDescriptionTextPart1 product description",
            'product_category_id' => $category1->id,
        ]);

        $product2->update([
            'name' => "$productNamePart2 product name",
            'description_text' => "$productDescriptionTextPart2 product description",
            'product_category_id' => $category2->id,
        ]);

        // assert by product name
        $this->assertSearchIds(['q' => $productNamePart1], [$product1->id], ['products']);
        $this->assertSearchIds(['q' => $productNamePart2], [$product2->id], ['products']);

        // assert by product description
        $this->assertSearchIds(['q' => $productDescriptionTextPart1], [$product1->id], ['products']);
        $this->assertSearchIds(['q' => $productDescriptionTextPart2], [$product2->id], ['products']);

        // assert by product category name
        $this->assertSearchIds(['q' => $categoryNamePart1], [$product1->id], ['products']);
        $this->assertSearchIds(['q' => $categoryNamePart2], [$product2->id], ['products']);

        // assert by organization name
        $this->assertSearchIds(['q' => $organizationNamePart1], [$product1->id], ['products']);
        $this->assertSearchIds(['q' => $organizationNamePart2], [$product2->id], ['products']);
    }

    /**
     * @throws Exception
     * @return void
     */
    public function testFiltersByFundQuery(): void
    {
        $orgNamePart1 = 'webshopgenericfundorganizationone';
        $orgNamePart2 = 'webshopgenericfundorganizationtwo';

        $namePart1 = 'webshopgenericfundnameone';
        $namePart2 = 'webshopgenericfundnametwo';

        $descriptionTextPart1 = 'webshopgenericfunddescriptionone';
        $descriptionTextPart2 = 'webshopgenericfunddescriptiontwo';

        $descriptionShortPart1 = 'webshopgenericfundshortone';
        $descriptionShortPart2 = 'webshopgenericfundshorttwo';

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
        $this->assertSearchIds(['q' => $orgNamePart1], [$fund1->id], ['funds']);
        $this->assertSearchIds(['q' => $orgNamePart2], [$fund2->id], ['funds']);

        // assert by fund name
        $this->assertSearchIds(['q' => $namePart1], [$fund1->id], ['funds']);
        $this->assertSearchIds(['q' => $namePart2], [$fund2->id], ['funds']);

        // assert by description_text
        $this->assertSearchIds(['q' => $descriptionTextPart1], [$fund1->id], ['funds']);
        $this->assertSearchIds(['q' => $descriptionTextPart2], [$fund2->id], ['funds']);

        // assert by description_short
        $this->assertSearchIds(['q' => $descriptionShortPart1], [$fund1->id], ['funds']);
        $this->assertSearchIds(['q' => $descriptionShortPart2], [$fund2->id], ['funds']);
    }

    /**
     * @throws Exception
     * @return void
     */
    public function testFiltersByProviderQuery(): void
    {
        $namePart1 = 'webshopprovidernameone';
        $namePart2 = 'webshopprovidernametwo';

        $emailPart1 = 'webshopprovideremailone';
        $emailPart2 = 'webshopprovideremailtwo';

        $phonePart1 = '9922334411';
        $phonePart2 = '9955667711';

        $websitePart1 = 'webshopprovidersiteone';
        $websitePart2 = 'webshopprovidersitetwo';

        $sponsor = $this->makeTestOrganization($this->makeIdentity());

        $provider1 = $this->makeTestProviderOrganization($this->makeIdentity(), [
            'name' => "$namePart1 name",
            'email' => $this->makeUniqueEmail($emailPart1),
            'email_public' => true,
            'phone' => "{$phonePart1}444",
            'phone_public' => true,
            'website' => "https://$websitePart1.example.com",
            'website_public' => true,
        ]);

        $provider2 = $this->makeTestProviderOrganization($this->makeIdentity(), [
            'name' => "$namePart2 name",
            'email' => $this->makeUniqueEmail($emailPart2),
            'email_public' => true,
            'phone' => "{$phonePart2}444",
            'phone_public' => true,
            'website' => "https://$websitePart2.example.com",
            'website_public' => true,
        ]);

        $fund = $this->makeTestFund($sponsor);
        $this->makeTestFundProvider($provider1, $fund);
        $this->makeTestFundProvider($provider2, $fund);

        $this->assertSearchIds(['q' => $namePart1], [$provider1->id], ['providers']);
        $this->assertSearchIds(['q' => $namePart2], [$provider2->id], ['providers']);

        $this->assertSearchIds(['q' => $emailPart1], [$provider1->id], ['providers']);
        $this->assertSearchIds(['q' => $emailPart2], [$provider2->id], ['providers']);

        $this->assertSearchIds(['q' => $phonePart1], [$provider1->id], ['providers']);
        $this->assertSearchIds(['q' => $phonePart2], [$provider2->id], ['providers']);

        $this->assertSearchIds(['q' => $websitePart1], [$provider1->id], ['providers']);
        $this->assertSearchIds(['q' => $websitePart2], [$provider2->id], ['providers']);
    }

    /**
     * @throws Exception
     * @return void
     */
    public function testFiltersByQueryAllTypesByName(): void
    {
        $namePart1 = 'webshopgenericallnameone';
        $namePart2 = 'webshopgenericallnametwo';

        $sponsor = $this->makeTestOrganization($this->makeIdentity());

        $fund1 = $this->makeTestFund($sponsor, ['name' => "$namePart1 fund name"]);
        $fund2 = $this->makeTestFund($sponsor, ['name' => "$namePart2 fund name"]);

        $provider1 = $this->makeTestProviderOrganization($this->makeIdentity(), ['name' => "$namePart1 organization"]);
        $provider2 = $this->makeTestProviderOrganization($this->makeIdentity(), ['name' => "$namePart2 organization"]);

        $product1 = $this->createProductForReservation($provider1, [$fund1]);
        $product1->update(['name' => "$namePart1 product name"]);

        $product2 = $this->createProductForReservation($provider2, [$fund2]);
        $product2->update(['name' => "$namePart2 product name"]);

        $this->assertSearchIds(['q' => $namePart1], [
            $fund1->id,
            $product1->id,
            $provider1->id,
        ], ['funds', 'products', 'providers']);

        $this->assertSearchIds(['q' => $namePart2], [
            $fund2->id,
            $product2->id,
            $provider2->id,
        ], ['funds', 'products', 'providers']);
    }

    /**
     * @throws Exception
     * @return void
     */
    public function testFiltersByFundId(): void
    {
        $sponsor = $this->makeTestOrganization($this->makeIdentity());

        $fund1 = $this->makeTestFund($sponsor);
        $fund2 = $this->makeTestFund($sponsor);

        $provider1 = $this->makeTestProviderOrganization($this->makeIdentity());
        $provider2 = $this->makeTestProviderOrganization($this->makeIdentity());

        $product1 = $this->createProductForReservation($provider1, [$fund1]);
        $product2 = $this->createProductForReservation($provider2, [$fund2]);

        $this->assertSearchIds(['fund_id' => $fund1->id], [
            $fund1->id,
            $product1->id,
            $provider1->id,
        ], ['funds', 'products', 'providers']);

        $this->assertSearchIds(['fund_id' => $fund1->id], [$fund1->id], ['funds']);
        $this->assertSearchIds(['fund_id' => $fund1->id], [$product1->id], ['products']);
        $this->assertSearchIds(['fund_id' => $fund1->id], [$provider1->id], ['providers']);

        $this->assertSearchIds(['fund_id' => $fund2->id], [
            $fund2->id,
            $product2->id,
            $provider2->id,
        ], ['funds', 'products', 'providers']);

        $this->assertSearchIds(['fund_id' => $fund2->id], [$fund2->id], ['funds']);
        $this->assertSearchIds(['fund_id' => $fund2->id], [$product2->id], ['products']);
        $this->assertSearchIds(['fund_id' => $fund2->id], [$provider2->id], ['providers']);
    }

    /**
     * @throws Exception
     * @return void
     */
    public function testFiltersByOrganizationId(): void
    {
        $sponsor = $this->makeTestOrganization($this->makeIdentity());

        $fund1 = $this->makeTestFund($sponsor);
        $fund2 = $this->makeTestFund($sponsor);

        $provider1 = $this->makeTestProviderOrganization($this->makeIdentity());
        $provider2 = $this->makeTestProviderOrganization($this->makeIdentity());

        $product1 = $this->createProductForReservation($provider1, [$fund1]);
        $product2 = $this->createProductForReservation($provider2, [$fund2]);

        $this->assertSearchIds(['organization_id' => $sponsor->id], [
            $fund1->id,
            $fund2->id,
        ], ['funds', 'products', 'providers']);

        $this->assertSearchIds(['organization_id' => $provider1->id], [
            $product1->id,
            $provider1->id,
        ], ['funds', 'products', 'providers']);

        $this->assertSearchIds(['organization_id' => $provider2->id], [
            $product2->id,
            $provider2->id,
        ], ['funds', 'products', 'providers']);

        $this->assertSearchIds(['organization_id' => $provider1->id], [$provider1->id], ['providers']);
        $this->assertSearchIds(['organization_id' => $provider2->id], [$provider2->id], ['providers']);

        $this->assertSearchIds(['organization_id' => $provider1->id], [$product1->id], ['products']);
        $this->assertSearchIds(['organization_id' => $provider2->id], [$product2->id], ['products']);

        $this->assertSearchIds(['organization_id' => $provider1->id], [], ['funds']);
        $this->assertSearchIds(['organization_id' => $provider2->id], [], ['funds']);

        $this->assertSearchIds(['organization_id' => $sponsor->id], [$fund1->id, $fund2->id], ['funds']);
    }

    /**
     * @throws Exception
     * @return void
     */
    public function testFiltersByWithExternal(): void
    {
        $sponsor = $this->makeTestOrganization($this->makeIdentity());

        $fund1 = $this->makeTestFund($sponsor, ['external' => true]);
        $fund2 = $this->makeTestFund($sponsor);

        $this->assertSearchIds([
            'organization_id' => $sponsor->id,
            'with_external' => false,
        ], [$fund2->id], ['funds', 'products', 'providers']);

        $this->assertSearchIds([
            'organization_id' => $sponsor->id,
            'with_external' => true,
        ], [$fund1->id, $fund2->id], ['funds', 'products', 'providers']);
    }

    /**
     * @throws Exception
     * @return void
     */
    public function testFiltersByProductCategoryId(): void
    {
        $sponsor = $this->makeTestOrganization($this->makeIdentity());

        $fund1 = $this->makeTestFund($sponsor);
        $fund2 = $this->makeTestFund($sponsor);

        $provider1 = $this->makeTestProviderOrganization($this->makeIdentity());
        $provider2 = $this->makeTestProviderOrganization($this->makeIdentity());

        $product1 = $this->createProductForReservation($provider1, [$fund1]);
        $product2 = $this->createProductForReservation($provider2, [$fund2]);

        $category1 = $this->makeProductCategory();
        $category2 = $this->makeProductCategory();

        $product1->update([
            'product_category_id' => $category1->id,
        ]);

        $product2->update([
            'product_category_id' => $category2->id,
        ]);

        $this->assertSearchIds(['product_category_id' => $category1->id], [
            $product1->id,
            $provider1->id,
        ], ['products', 'providers']);

        $this->assertSearchIds(['product_category_id' => $category1->id], [$product1->id], ['products']);
        $this->assertSearchIds(['product_category_id' => $category1->id], [$provider1->id], ['providers']);

        $this->assertSearchIds(['product_category_id' => $category2->id], [
            $product2->id,
            $provider2->id,
        ], ['products', 'providers']);

        $this->assertSearchIds(['product_category_id' => $category2->id], [$product2->id], ['products']);
        $this->assertSearchIds(['product_category_id' => $category2->id], [$provider2->id], ['providers']);
    }

    /**
     * @throws Exception
     * @return void
     */
    public function testFiltersByPostcodeAndDistance(): void
    {
        $identity = $this->makeIdentity();
        $sponsor = $this->makeTestOrganization($identity);
        $provider1 = $this->makeTestProviderOrganization($this->makeIdentity());
        $provider2 = $this->makeTestProviderOrganization($this->makeIdentity());

        $fund = $this->makeTestFund($sponsor);

        $product1 = $this->createProductForReservation($provider1, [$fund]);
        $product2 = $this->createProductForReservation($provider2, [$fund]);

        $this->makeOrganizationOffice($provider1, [
            'postcode' => '9721 AN',
            'lat' => 53.1935717,
            'lon' => 6.5825892,
        ]);

        $this->makeOrganizationOffice($provider2, [
            'postcode' => '9721 AN',
            'lat' => 43.1935717,
            'lon' => 6.5825892,
        ]);

        $this->assertSearchIds([
            'fund_id' => $fund->id,
            'postcode' => '9721 AN',
            'distance' => 5,
        ], [$product1->id, $provider1->id], ['products', 'providers']);

        $this->assertSearchIds([
            'fund_id' => $fund->id,
            'postcode' => '9721 AN',
            'distance' => 5,
        ], [$product1->id], ['products']);

        $this->assertSearchIds([
            'fund_id' => $fund->id,
            'postcode' => '9721 AN',
            'distance' => 5,
        ], [$provider1->id], ['providers']);

        $this->assertSearchIds([
            'fund_id' => $fund->id,
            'postcode' => '9721 AN',
            'distance' => 10000,
        ], [$product1->id, $product2->id, $provider1->id, $provider2->id], ['products', 'providers']);

        $this->assertSearchIds([
            'fund_id' => $fund->id,
            'postcode' => '9721 AN',
            'distance' => 10000,
        ], [$product1->id, $product2->id], ['products']);

        $this->assertSearchIds([
            'fund_id' => $fund->id,
            'postcode' => '9721 AN',
            'distance' => 10000,
        ], [$provider1->id, $provider2->id], ['providers']);
    }

    /**
     * @throws Exception
     * @return void
     */
    public function testOrdersByCreatedAt(): void
    {
        $now = Carbon::now();
        $sponsor = $this->makeTestOrganization($this->makeIdentity());

        $fundA = $this->makeTestFund($sponsor);
        Carbon::setTestNow($now->clone()->addDay());
        $fundB = $this->makeTestFund($sponsor);

        $providerA = $this->makeTestProviderOrganization($this->makeIdentity());

        Carbon::setTestNow($now->clone()->addDays(2));
        $productA = $this->createProductForReservation($providerA, [$fundA]);

        Carbon::setTestNow($now->clone()->addDays(3));
        $providerB = $this->makeTestProviderOrganization($this->makeIdentity());

        Carbon::setTestNow($now->clone()->addDays(4));
        $productB = $this->createProductForReservation($providerB, [$fundA]);

        $this->assertSearchOrder([
            'organization_id' => $sponsor->id,
            'order_by' => 'created_at',
            'order_dir' => 'asc',
        ], [$fundA->id, $fundB->id], ['funds']);

        $this->assertSearchOrder([
            'organization_id' => $sponsor->id,
            'order_by' => 'created_at',
            'order_dir' => 'desc',
        ], [$fundB->id, $fundA->id], ['funds']);

        $this->assertSearchOrder([
            'fund_id' => $fundA->id,
            'order_by' => 'created_at',
            'order_dir' => 'asc',
        ], [$providerA->id, $productA->id, $providerB->id, $productB->id], ['products', 'providers']);

        $this->assertSearchOrder([
            'fund_id' => $fundA->id,
            'order_by' => 'created_at',
            'order_dir' => 'desc',
        ], [$productB->id, $providerB->id, $productA->id, $providerA->id], ['products', 'providers']);
    }

    /**
     * @param array $filters
     * @return WebshopGenericSearch
     */
    private function makeSearch(array $filters): WebshopGenericSearch
    {
        return new WebshopGenericSearch($filters);
    }

    /**
     * @param array $filters
     * @param array $expectedIds
     * @param array $types
     * @throws Exception
     * @return void
     */
    private function assertSearchIds(array $filters, array $expectedIds, array $types): void
    {
        $expected = collect($expectedIds)->sort()->values()->toArray();
        $search = $this->makeSearch($filters);
        $actual = collect($search->query($types)->pluck('id')->toArray())->sort()->values()->toArray();

        $this->assertSame($expected, $actual);
    }

    /**
     * @param array $filters
     * @param array $expectedIds
     * @param array $types
     * @throws Exception
     * @return void
     */
    private function assertSearchOrder(array $filters, array $expectedIds, array $types): void
    {
        $search = $this->makeSearch($filters);
        $actual = $search->query($types)->pluck('id')->toArray();

        $this->assertSame($expectedIds, $actual);
    }
}
