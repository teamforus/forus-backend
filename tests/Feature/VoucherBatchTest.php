<?php

namespace Tests\Feature;

use App\Models\Fund;
use App\Models\Product;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use Tests\TestCases\VoucherBatchTestCases;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\VoucherTestTrait;
use Throwable;

class VoucherBatchTest extends TestCase
{
    use VoucherTestTrait;
    use DatabaseTransactions;
    use MakesTestFunds;

    /**
     * @var string
     */
    protected string $apiUrl = '/api/v1/platform/organizations/%s/sponsor/vouchers/batch';

    /**
     * @throws Throwable
     * @return void
     */
    public function testVoucherBatchCaseBudgetVouchers(): void
    {
        $this->processVoucherBatchTestCase(VoucherBatchTestCases::$featureTestCaseBudgetVouchers);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testVoucherBatchCaseProductVouchers(): void
    {
        $this->processVoucherBatchTestCase(VoucherBatchTestCases::$featureTestCaseProductVouchers);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testVoucherBatchCaseBudgetVouchersAllowedDirectPayments(): void
    {
        $this->processVoucherBatchTestCase(VoucherBatchTestCases::$featureTestCaseBudgetVouchersAllowedDirectPayments);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testVoucherBatchCaseBudgetVouchersNoBSNExceedAmount(): void
    {
        $this->processVoucherBatchTestCase(VoucherBatchTestCases::$featureTestCaseBudgetVouchersNoBSNExceedAmount);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testVoucherBatchCaseBudgetAndProductVouchersEdgeCases(): void
    {
        $this->processVoucherBatchTestCase(VoucherBatchTestCases::$featureTestCaseBudgetAndProductVouchersEdgeCases);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testVoucherBatchCaseBudgetVouchersSameAssign(): void
    {
        $this->processVoucherBatchTestCase(VoucherBatchTestCases::$featureTestCaseBudgetVouchersSameAssign);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testVoucherBatchCaseBudgetVouchersAllowedDirectPaymentsErrors(): void
    {
        $this->processVoucherBatchTestCase(VoucherBatchTestCases::$featureTestCaseBudgetVouchersAllowedDirectPaymentsErrors);
    }

    /**
     * @throws Throwable
     */
    protected function processVoucherBatchTestCase(array $testCase): void
    {
        Cache::clear();

        $fund = $this->findFund($testCase['fund_id']);

        $fund->fund_config->forceFill($testCase['fund_config'] ?? [])->save();
        $fund->organization->forceFill($testCase['organization'] ?? [])->save();

        $this->addTestCriteriaToFund($fund);
        $products = $this->makeProviderAndProducts($fund);

        $this->setFundFormulaProductsForFund($fund, array_random($products['approved'], 3), 'test_number');

        // create vouchers
        foreach ($testCase['asserts'] as $assert) {
            $this->storeVouchers($fund, $assert, $products[$assert['product'] ?? 'approved']);
        }
    }

    /**
     * @param Fund $fund
     * @param array $assert
     * @param Product[] $products
     * @throws Throwable
     * @return void
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
            $vouchersBuilder->each(fn (Voucher $voucher) => $this->deleteVoucher($voucher));
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
