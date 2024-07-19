<?php

namespace Tests\Feature;

use App\Models\Fund;
use App\Models\Product;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Tests\TestCases\VoucherBatchTestCases;
use Tests\TestCase;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\VoucherTestTrait;

class VoucherBatchTest extends TestCase
{
    use VoucherTestTrait, DatabaseTransactions, MakesTestFunds;

    /**
     * @var string
     */
    protected string $apiUrl = '/api/v1/platform/organizations/%s/sponsor/vouchers/batch';

    /**
     * @return void
     * @throws \Throwable
     */
    public function testVoucherBatchCase1(): void
    {
        $this->processVoucherBatchTestCase(VoucherBatchTestCases::$featureTestCase1);
    }

    /**
     * @return void
     * @throws \Throwable
     */
    public function testVoucherBatchCase2(): void
    {
        $this->processVoucherBatchTestCase(VoucherBatchTestCases::$featureTestCase2);
    }

    /**
     * @return void
     * @throws \Throwable
     */
    public function testVoucherBatchCase3(): void
    {
        $this->processVoucherBatchTestCase(VoucherBatchTestCases::$featureTestCase3);
    }

    /**
     * @return void
     * @throws \Throwable
     */
    public function testVoucherBatchCase4(): void
    {
        $this->processVoucherBatchTestCase(VoucherBatchTestCases::$featureTestCase4);
    }

    /**
     * @return void
     * @throws \Throwable
     */
    public function testVoucherBatchCase5(): void
    {
        $this->processVoucherBatchTestCase(VoucherBatchTestCases::$featureTestCase5);
    }

    /**
     * @return void
     * @throws \Throwable
     */
    public function testVoucherBatchCase6(): void
    {
        $this->processVoucherBatchTestCase(VoucherBatchTestCases::$featureTestCase6);
    }

    /**
     * @return void
     * @throws \Throwable
     */
    public function testVoucherBatchCase7(): void
    {
        $this->processVoucherBatchTestCase(VoucherBatchTestCases::$featureTestCase7);
    }

    /**
     * @throws \Throwable
     */
    protected function processVoucherBatchTestCase(array $testCase): void
    {
        Cache::clear();

        $fund = $this->findFund($testCase['fund_id']);

        $fund->fund_config->forceFill($testCase['fund_config'] ?? [])->save();
        $fund->organization->forceFill($testCase['organization'] ?? [])->save();

        $this->addTestCriteriaToFund($fund);
        $products = $this->makeProviderAndProducts($fund);

        if ($fund->isTypeBudget()) {
            $this->setFundFormulaProductsForFund($fund, array_random($products['approved'], 3), 'test_number');
        }

        // create vouchers
        foreach ($testCase['asserts'] as $assert) {
            $this->storeVouchers($fund, $assert, $products[$assert['product'] ?? 'approved']);
        }
    }

    /**
     * @param Fund $fund
     * @param array $assert
     * @param Product[] $products
     * @return void
     * @throws \Throwable
     */
    protected function storeVouchers(Fund $fund, array $assert, array $products): void
    {
        $startDate = now();
        $headers = $this->makeApiHeaders($this->makeIdentityProxy($fund->organization->identity));

        $data = [
            'fund_id' => $fund->id,
            'vouchers' => $this->makeVoucherData($fund, $assert, $products),
        ];

        $validateResponse = $this->postJson($this->getApiUrl($fund, '/validate'), $data, $headers);
        $uploadResponse = $this->postJson($this->getApiUrl($fund), $data, $headers);

        if ($assert['assert_created']) {
            $validateResponse->assertSuccessful();
            $uploadResponse->assertSuccessful();

            $vouchersBuilder = $this->getVouchersBuilder($fund, $startDate, $assert['type'] ?? 'budget');
            $this->assertVouchersCreated($vouchersBuilder, $startDate, $data['vouchers'], $assert);
            $vouchersBuilder->delete();
        } else {
            $validateResponse->assertJsonValidationErrors($assert['assert_errors'] ?? []);
            $uploadResponse->assertJsonValidationErrors($assert['assert_errors'] ?? []);
        }
    }

    /**
     * @param Fund $fund
     * @param string $append
     * @return string
     */
    protected function getApiUrl(Fund $fund, string $append = ''): string
    {
        return sprintf($this->apiUrl, $fund->organization->id) . $append;
    }
}
