<?php

namespace Tests\Feature;

use App\Models\BusinessType;
use App\Models\Fund;
use App\Models\Organization;
use App\Models\ProductCategory;
use App\Models\VoucherTransaction;
use App\Traits\DoesTesting;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Arr;
use Illuminate\Testing\TestResponse;
use Tests\TestCases\SponsorFinancialStatisticsTestCases;
use Tests\CreatesApplication;
use Tests\TestCase;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestIdentities;
use Tests\Traits\MakesTestOrganizationOffices;
use Tests\Traits\MakesTestOrganizations;

class SponsorFinancialStatisticsTest extends TestCase
{
    use WithFaker;
    use DoesTesting;
    use MakesTestFunds;
    use CreatesApplication;
    use MakesTestIdentities;
    use DatabaseTransactions;
    use MakesTestOrganizations;
    use MakesTestOrganizationOffices;

    public array $mapFunds = [];
    public array $mapProviders = [];
    public array $mapCategories = [];
    public array $mapBusinessTypes = [];

    /**
     * @return void
     */
    public function testFundStatisticMonth()
    {
        $this->processTestCase(SponsorFinancialStatisticsTestCases::$testCaseFundStatisticMonth);
    }

    /**
     * @return void
     */
    public function testFundStatisticQuarter()
    {
        $this->processTestCase(SponsorFinancialStatisticsTestCases::$testCaseFundStatisticQuarter);
    }

    /**
     * @return void
     */
    public function testFundStatisticYear()
    {
        $this->processTestCase(SponsorFinancialStatisticsTestCases::$testCaseFundStatisticYear);
    }

    /**
     * @param array $testCase
     * @return void
     */
    public function processTestCase(array $testCase): void
    {
        $identity = $this->makeIdentity();
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $this->createFundsAndVouchers($organization, $testCase);

        // create another organization for same identity
        // and create funds, vouchers, transactions with same case - these data
        // should not be visible for first organization
        $otherOrganization = $this->makeTestOrganization($identity);
        $this->createFundsAndVouchers($otherOrganization, $testCase);

        // assert base data
        $this->assertFinances($organization, $testCase['type'], $testCase['assert']);
        $this->assertFilterCounts($organization, $testCase);

        // assert filtered data
        foreach ($testCase['filters'] as $filter) {
            $this->assertFinances(
                $organization,
                $testCase['type'],
                $filter['assert'],
                $this->mapFilterParams($filter['params']),
            );
        }
    }

    /**
     * @param Organization $organization
     * @param array $testCase
     * @return void
     */
    private function createFundsAndVouchers(Organization $organization, array $testCase): void
    {
        foreach ($testCase['funds'] as $fundCases) {
            $this->travelTo($testCase['date']);

            $fund = $this->createFundAndTopUpBudget($organization, 40000, [
                'name' => $fundCases['name']
            ]);

            $this->mapFunds = [
                $fund->name => $fund->id,
                ...$this->mapFunds,
            ];

            if ($fundCases['closed'] ?? false) {
                $fund->update(['state' => Fund::STATE_CLOSED]);
            }

            $this->travelTo($fundCases['date']);
            $this->makeVouchersWithTransactions($fund, $fundCases['vouchers']);
        }
    }

    /**
     * @param Organization $organization
     * @param float|int $topUpAmount
     * @param array $fundData
     * @return Fund
     */
    private function createFundAndTopUpBudget(
        Organization $organization,
        float|int $topUpAmount,
        array $fundData = [],
    ): Fund {
        $fund = $this->makeTestFund($organization, [
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            ...$fundData,
        ]);

        $fund->top_ups()->forceDelete();
        $fund->getOrCreateTopUp()->transactions()->create(['amount' => $topUpAmount]);

        return $fund;
    }

    /**
     * @param Fund $fund
     * @param array $vouchersArr
     * @return void
     */
    public function makeVouchersWithTransactions(Fund $fund, array $vouchersArr): void
    {
        $employee = $fund->organization->employees[0];

        foreach ($vouchersArr as $item) {
            if ($item['type'] === 'budget') {
                $voucher = $fund->makeVoucher($this->makeIdentity());

                $voucher
                    ->makeTransactionBySponsor($employee, ['amount' => $item['transaction_amount']])
                    ->setPaid(null, now());
            } elseif ($item['type'] === 'product') {
                /** @var ProductCategory $category */
                $category = ProductCategory::where(['key' => $item['category']])->first();

                if (!$category) {
                    /** @var ProductCategory $baseCategory */
                    $baseCategory = ProductCategory::firstOrCreate([
                        'key' => "base_{$item['category']}"
                    ]);

                    $category = $baseCategory->descendants()->create([
                        'key' => $item['category'],
                        'parent_id' => $baseCategory->id,
                        'root_id' => $baseCategory->id,
                    ]);
                }

                $this->mapCategories = [
                    $item['category'] => $category->root_id ?? $category->id,
                    ...$this->mapCategories,
                ];

                /** @var BusinessType $businessType */
                $businessType = BusinessType::firstOrCreate(['key' => $item['business_type']]);

                $this->mapBusinessTypes = [
                    $item['business_type'] => $businessType->id,
                    ...$this->mapBusinessTypes,
                ];

                $customProvider = $item['provider'] ?? false;
                if (!($provider = Organization::find($this->mapProviders[$customProvider] ?? null))) {
                    $identity = $this->makeIdentity($this->makeUniqueEmail('provider_'));
                    $provider = $this->makeTestProviderOrganization($identity, [
                        'business_type_id' => $businessType->id,
                    ]);
                }

                if ($customProvider) {
                    $this->mapProviders = [
                        $item['provider'] => $provider->id,
                        ...$this->mapProviders,
                    ];
                }

                $officeExists = $provider->offices()
                    ->where('postcode_number', $item['provider_office_postcode_number'])
                    ->exists();

                if (!$officeExists) {
                    $this->makeOrganizationOffice($provider, [
                        'postcode_number' => $item['provider_office_postcode_number']
                    ]);
                }

                $product = $this->makeTestProduct($provider, [
                    'price' => $item['product_price'],
                    'product_category_id' => $category->id,
                ]);

                $this->addProductFundToFund($fund, $product, false);
                $voucher = $fund->makeProductVoucher($this->makeIdentity(), [], $product->id);

                $params = [
                    'amount' => $voucher->amount,
                    'product_id' => $voucher->product_id,
                    'employee_id' => $employee?->id,
                    'branch_id' => $employee?->office?->branch_id,
                    'branch_name' => $employee?->office?->branch_name,
                    'branch_number' => $employee?->office?->branch_number,
                    'target' => VoucherTransaction::TARGET_PROVIDER,
                    'organization_id' => $voucher->product->organization_id,
                ];

                $voucher->makeTransaction($params)->setPaid(null, now());
            }
        }
    }

    /**
     * @param Organization $organization
     * @param string $type
     * @param array $assert
     * @param array $query
     * @return void
     */
    private function assertFinances(
        Organization $organization,
        string $type,
        array $assert,
        array $query = [],
    ): void {
        $query = [
            ...$query,
            'type' => $type,
            'type_value' => $assert['date'],
        ];

        $url = "/api/v1/platform/organizations/$organization->id/sponsor/finances";
        $url .= '?' . http_build_query($query);

        $response = $this->getJson($url, $this->makeApiHeaders($organization->identity));
        $response->assertSuccessful();

        $this->assertByType($type, $response->json('dates'), $assert);
        $this->assertTotals($response, $assert);
        $this->assertProviders($organization, $assert, $query);
    }

    /**
     * @param string $type
     * @param array $dates
     * @param array $testCaseAssertion
     * @return void
     */
    private function assertByType(string $type, array $dates, array $testCaseAssertion): void
    {
        $transactions = $testCaseAssertion['transactions'];
        $dateFrom = Carbon::createFromFormat('Y-m-d', $testCaseAssertion['date']);
        [$dateFrom, $dateTo] = $this->prepareDatesByType($dateFrom, $type);

        foreach ($dates as $data) {
            $assertDates = array_keys($transactions);

            if ($date = $this->getAssertionDateBetween($dateFrom, $dateTo, $assertDates)) {
                $key = $date->toDateString();
                $message = "Transaction count for type '$type' with date '$key' not equal expected";
                $this->assertEquals($transactions[$key]['count'], $data['count'], $message);

                $message = "Transaction amount for type '$type' with date '$key' not equal expected";
                $this->assertEquals(
                    currency_format($transactions[$key]['amount']),
                    $data['amount'],
                    $message
                );
            } else {
                $baseMessage = "Transaction %s for type '$type' between dates " .
                    "'{$dateFrom->toDateString()} - {$dateTo->toDateString()}' must be %s";

                $this->assertEquals(0, $data['count'], sprintf($baseMessage, 'count', '0'));
                $this->assertNull($data['amount'], sprintf($baseMessage, 'amount', 'nullable'));
            }

            [$dateFrom, $dateTo] = $this->increaseDatesByType($dateFrom, $type);
        }
    }

    /**
     * @param Carbon $dateFrom
     * @param string $type
     * @return array
     */
    private function prepareDatesByType(Carbon $dateFrom, string $type): array
    {
        switch ($type) {
            case 'month':
                $dateFrom->startOfMonth();
                $dateTo = $dateFrom->copy();
                break;
            case 'quarter':
                $dateFrom->startOfQuarter();
                $dateTo = $dateFrom->copy()->endOfWeek();
                break;
            case 'year':
                $dateFrom->startOfYear();
                $dateTo = $dateFrom->copy()->endOfMonth();
                break;
            default:
                $dateTo = $dateFrom->copy();
        }

        return [$dateFrom, $dateTo];
    }

    /**
     * @param Carbon $dateFrom
     * @param string $type
     * @return array
     */
    private function increaseDatesByType(Carbon $dateFrom, string $type): array
    {
        switch ($type) {
            case 'month':
                $dateFrom->addDay();
                $dateTo = $dateFrom->copy();
                break;
            case 'quarter':
                $dateFrom->addWeek()->startOfWeek();
                $dateTo = $dateFrom->copy()->endOfWeek();
                break;
            case 'year':
                $dateFrom->addMonth()->startOfMonth();
                $dateTo = $dateFrom->copy()->endOfMonth();
                break;
            default:
                $dateTo = $dateFrom->copy();
        }

        return [$dateFrom, $dateTo];
    }

    /**
     * @param Carbon $from
     * @param Carbon $to
     * @param array $dates
     * @return Carbon|null
     */
    private function getAssertionDateBetween(Carbon $from, Carbon $to, array $dates): ?Carbon
    {
        return collect($dates)
            ->map(fn(string $date) => Carbon::createFromFormat('Y-m-d', $date))
            ->first(fn(Carbon $date) => $date->between($from, $to));
    }

    /**
     * @param TestResponse $response
     * @param array $assert
     * @return void
     */
    private function assertTotals(TestResponse $response, array $assert): void
    {
        $response->assertJsonPath('totals.count', $assert['count']);
        $response->assertJsonPath('totals.amount', $assert['amount']);

        // assert the highest transaction amount
        $highestTransaction = is_null($assert['highest_transaction'])
            ? null
            : currency_format($assert['highest_transaction']);

        $response->assertJsonPath('highest_transaction.amount', $highestTransaction);

        // assert highest daily transaction amount
        $highestDailyTransaction = is_null($assert['highest_daily_transaction'])
            ? null
            : currency_format($assert['highest_daily_transaction']);

        $response->assertJsonPath('highest_daily_transaction.amount', $highestDailyTransaction);

        $response->assertJsonPath(
            'highest_daily_transaction.date',
            $assert['highest_daily_transaction_date']
        );
    }

    /**
     * @param Organization $organization
     * @param array $assert
     * @param array $query
     * @return void
     */
    private function assertProviders(
        Organization $organization,
        array $assert,
        array $query = [],
    ): void {
        $url = "/api/v1/platform/organizations/$organization->id/sponsor/providers/finances";
        $url .= '?' . http_build_query($query);

        $response = $this->getJson($url, $this->makeApiHeaders($organization->identity));
        $response->assertSuccessful();

        $providers = $assert['providers'] ?? [];
        $data = $response->json('data');

        foreach ($providers as $key => $providerAsset) {
            $providerId = $this->mapProviders[$key];

            $provider = Arr::first($data, function (array $value) use ($providerId) {
                return $value['provider']['id'] === $providerId;
            });

            $this->assertNotNull($provider);
            $this->assertEquals($providerAsset['count'], $provider['nr_transactions']);

            $this->assertEquals(
                $providerAsset['total_spent'],
                currency_format($provider['total_spent'])
            );

            $this->assertEquals(
                $providerAsset['highest_transaction'],
                currency_format($provider['highest_transaction'])
            );

            $this->assertProviderTransactions($organization, $providerAsset['transactions'] ?? [], [
                ...$query,
                'provider_ids' => [$providerId],
            ]);
        }
    }

    /**
     * @param Organization $organization
     * @param array $assert
     * @param array $query
     * @return void
     */
    private function assertProviderTransactions(
        Organization $organization,
        array $assert,
        array $query = [],
    ): void {
        $url = "/api/v1/platform/organizations/$organization->id/sponsor/transactions";
        $url .= '?' . http_build_query($query);

        $response = $this->getJson($url, $this->makeApiHeaders($organization->identity));
        $response->assertSuccessful();

        $data = $response->json('data');

        foreach ($assert as $transactionAsset) {
            $transaction = Arr::first($data, function (array $value) use ($transactionAsset) {
                return $value['amount'] === currency_format($transactionAsset['amount']);
            });

            $this->assertNotNull($transaction);
        }
    }

    /**
     * @param Organization $organization
     * @param array $testCase
     * @return void
     */
    private function assertFilterCounts(Organization $organization, array $testCase): void
    {
        $assert = $testCase['assert'];

        $response = $this->getJson(
            sprintf(
                "/api/v1/platform/organizations/%s/sponsor/finances?type=%s&type_value=%s&filters=1",
                $organization->id,
                $testCase['type'],
                $assert['date']
            ),
            $this->makeApiHeaders($organization->identity),
        );

        $response->assertSuccessful();

        $productCategories = $response->json('filters.product_categories');
        $assertCategories = $assert['filters']['categories'];

        foreach ($assertCategories as $key => $count) {
            $found = Arr::first($productCategories, fn($item) => $item['id'] === $this->mapCategories[$key]);
            $this->assertNotNull($found, "Not found product category $key");
            $message = "Filtered product category $key transactions count not equals";
            $this->assertEquals($count, $found['transactions'], $message);
        }

        $businessTypes = $response->json('filters.business_types');
        $assertBusinessTypes = $assert['filters']['business_types'];

        foreach ($assertBusinessTypes as $key => $count) {
            $found = Arr::first($businessTypes, fn($item) => $item['id'] === $this->mapBusinessTypes[$key]);
            $this->assertNotNull($found, "Not found business type $key");
            $message = "Filtered business type $key transactions count not equals";
            $this->assertEquals($count, $found['transactions'], $message);
        }

        $postCodes = $response->json('filters.postcodes');
        $assertPostCodes = $assert['filters']['postcodes'];

        foreach ($assertPostCodes as $key => $count) {
            $found = Arr::first($postCodes, fn($item) => (string)$item['id'] === (string)$key);
            $this->assertNotNull($found, "Not found postcode $key");
            $message = "Filtered postcode $key transactions count not equals";
            $this->assertEquals($count, $found['transactions'], $message);
        }

        $funds = $response->json('filters.funds');
        $assertFunds = $assert['filters']['funds'];

        foreach ($assertFunds as $key => $count) {
            $found = Arr::first($funds, fn($item) => $item['name'] === $key);
            $this->assertNotNull($found, "Not found fund $key");
            $message = "Filtered funds $key transactions count not equals";
            $this->assertEquals($count, $found['transactions'], $message);
        }
    }

    /**
     * @param array $params
     * @return array
     */
    private function mapFilterParams(array $params): array
    {
        $data = [
            'postcodes' => $params['postcodes']
        ];

        $data['product_category_ids'] = array_map(
            fn($item) => $this->mapCategories[$item], $params['product_categories']
        );

        $data['business_type_ids'] = array_map(
            fn($item) => $this->mapBusinessTypes[$item], $params['business_types']
        );

        $data['fund_ids'] = array_map(
            fn($item) => $this->mapFunds[$item], $params['funds']
        );

        return $data;
    }
}