<?php

namespace Tests\Feature;

use App\Models\Fund;
use App\Models\FundProvider;
use App\Models\Organization;
use App\Models\Product;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Arr;
use Tests\TestCase;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Throwable;

class ProductExclusionsTest extends TestCase
{
    use WithFaker;
    use MakesTestFunds;
    use DatabaseTransactions;
    use MakesTestOrganizations;

    /**
     * @return void
     * @throws Throwable
     */
    public function testProductExclusionForFund(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);
        $products = $this->makeProviderAndProducts($fund);

        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $proxy = $this->makeIdentityProxy($identity);
        $headers = $this->makeApiHeaders($proxy);

        /** @var Product $product */
        $product = $products['approved'][0] ?? null;
        $this->assertNotNull($product);

        $query = http_build_query(['per_page' => 1000]);
        $response = $this->getJson("/api/v1/platform/products?$query", $headers);
        $productIds = Arr::pluck($response->json('data'), 'id');
        $this->assertTrue(in_array($product->id, $productIds));

        /** @var FundProvider $fundProvider */
        $fundProvider = $product->fund_providers()->where('fund_id', $fund->id)->first();
        $this->assertNotNull($fundProvider);
        $product->product_exclusions()->create([
            'fund_provider_id' => $fundProvider->id,
        ]);

        $query = http_build_query(['per_page' => 1000]);
        $response = $this->getJson("/api/v1/platform/products?$query", $headers);
        $productIds = Arr::pluck($response->json('data'), 'id');
        $this->assertFalse(in_array($product->id, $productIds));
    }

    /**
     * @return void
     * @throws Throwable
     */
    public function testProductExclusionForSeveralFunds(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $funds = $this->makeFunds($organization);

        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $proxy = $this->makeIdentityProxy($identity);
        $headers = $this->makeApiHeaders($proxy);

        $firstFund = $funds[0];
        $this->assertNotNull($firstFund);

        // create products and approve for first fund
        $products = $this->makeProviderAndProducts($firstFund);

        /** @var Product $product */
        $product = $products['approved'][0] ?? null;
        $this->assertNotNull($product);

        array_walk($funds, fn ($fund) => $this->addProductFundToFund($fund, $product, false));

        $query = http_build_query(['per_page' => 1000]);
        $response = $this->getJson("/api/v1/platform/products?$query", $headers);

        $productIds = Arr::pluck($response->json('data'), 'id');
        $this->assertTrue(in_array($product->id, $productIds));

        foreach ($funds as $index => $fund) {
            /** @var FundProvider $fundProvider */
            $fundProvider = $product->fund_providers()->where('fund_id', $fund->id)->first();
            $this->assertNotNull($fundProvider);
            $product->product_exclusions()->create([
                'fund_provider_id' => $fundProvider->id,
            ]);

            foreach ($funds as $index2 => $fund2) {
                $query = http_build_query([
                    'fund_id' => $fund2->id,
                    'per_page' => 1000,
                ]);

                $response = $this->getJson("/api/v1/platform/products?$query", $headers);
                $productIds = Arr::pluck($response->json('data'), 'id');

                if ($index === $index2) {
                    $this->assertFalse(in_array($product->id, $productIds));
                } else {
                    $this->assertTrue(in_array($product->id, $productIds));
                }
            }

            $query = http_build_query([
                'per_page' => 1000,
            ]);

            // Product is approved as long as non excluded funds are still in the list
            $response = $this->getJson("/api/v1/platform/products?$query", $headers);
            $productIds = Arr::pluck($response->json('data'), 'id');
            $this->assertTrue(in_array($product->id, $productIds));

            // reset exclusions
            $product->product_exclusions()->forceDelete();
        }
    }

    /**
     * @return void
     * @throws Throwable
     */
    public function testProductExclusionForAllFunds(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $funds = $this->makeFunds($organization);

        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $proxy = $this->makeIdentityProxy($identity);
        $headers = $this->makeApiHeaders($proxy);

        /** @var Fund $firstFund */
        $firstFund = array_shift($funds);
        $this->assertNotNull($firstFund);

        // create products and approve for first fund
        $products = $this->makeProviderAndProducts($firstFund);

        /** @var Product $product */
        $product = $products['approved'][0] ?? null;
        $this->assertNotNull($product);

        $query = http_build_query(['per_page' => 1000]);
        $response = $this->getJson("/api/v1/platform/products?$query", $headers);

        $productIds = Arr::pluck($response->json('data'), 'id');
        $this->assertTrue(in_array($product->id, $productIds));

        foreach ($funds as $fund) {
            $query = http_build_query([
                'fund_id' => $fund->id,
                'per_page' => 1000,
            ]);

            $response = $this->getJson("/api/v1/platform/products?$query", $headers);
            $productIds = Arr::pluck($response->json('data'), 'id');
            $this->assertFalse(in_array($product->id, $productIds));
        }

        // approve for other funds
        array_walk($funds, fn ($fund) => $this->addProductFundToFund($fund, $product, false));

        foreach ($funds as $fund) {
            $query = http_build_query([
                'fund_id' => $fund->id,
                'per_page' => 1000,
            ]);

            $response = $this->getJson("/api/v1/platform/products?$query", $headers);
            $productIds = Arr::pluck($response->json('data'), 'id');
            $this->assertTrue(in_array($product->id, $productIds));
        }

        // exclude all funds
        $funds = [
            $firstFund,
            ...$funds,
        ];

        /** @var Fund $fund */
        foreach ($funds as $fund) {
            /** @var FundProvider $fundProvider */
            $fundProvider = $product->fund_providers()->where('fund_id', $fund->id)->first();
            $this->assertNotNull($fundProvider);
            $product->product_exclusions()->create([
                'fund_provider_id' => $fundProvider->id,
            ]);
        }

        $query = http_build_query(['per_page' => 1000]);
        $response = $this->getJson("/api/v1/platform/products?$query", $headers);
        $productIds = Arr::pluck($response->json('data'), 'id');
        $this->assertFalse(in_array($product->id, $productIds));
    }

    /**
     * @param Organization $organization
     * @return Fund[]
     */
    protected function makeFunds(Organization $organization): array
    {
        return array_reduce(range(1, 4), function ($list) use ($organization) {
            $list[] = $this->makeTestFund($organization);

            return $list;
        }, []);
    }
}
