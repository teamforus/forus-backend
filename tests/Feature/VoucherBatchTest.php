<?php

namespace Tests\Feature;

use App\Models\Fund;
use Tests\Configs\VoucherBatchTestConfig;
use Tests\TestCase;
use Tests\Traits\VoucherTestTrait;

class VoucherBatchTest extends TestCase
{
    use VoucherTestTrait;

    /**
     * @var string
     */
    protected string $apiUrl = '/api/v1/platform/organizations/%s/sponsor/vouchers/batch';

    /**
     * @return void
     * @throws \Throwable
     */
    public function testVoucherBatch(): void
    {
        foreach (VoucherBatchTestConfig::$featureTestCases as $testCase) {
            \DB::beginTransaction();
            $this->processProductStocksTestCase($testCase);
            $this->resetProperties();
            \DB::rollBack();
        }
    }

    /**
     * @throws \Throwable
     */
    protected function processProductStocksTestCase(array $testCase)
    {
        $fund = $this->findFund($testCase['fund_id']);
        $this->makeProviderAndProducts($fund, $testCase);
        $this->makeVouchers($fund, $testCase);
    }

    /**
     * @param Fund $fund
     * @param array $testCase
     * @return void
     * @throws \Throwable
     */
    protected function makeVouchers(Fund $fund, array $testCase): void
    {
        // configure fund
        $fund->fund_config()->update(array_only($testCase, [
            'allow_direct_payments', 'allow_generator_direct_payments',
        ]));

        // configure organization for bsn
        $fund->organization->update(['bsn_enabled' => $testCase['bsn_enabled']]);

        // create vouchers
        foreach ($testCase['asserts'] as $assert) {
            $type = $testCase['type'];
            $count = $assert['vouchers_count'] ?? $testCase['vouchers_count'];
            $range = range(0, $count - 1);

            $vouchers = array_reduce($range, function (array $arr, $index) use ($type, $assert, $fund) {
                $arr[] = array_except(
                    $this->getVoucherFields($fund, $type, $assert, $index, $arr),
                    $assert['except_fields'] ?? []
                );

                return $arr;
            }, []);

            $this->storeVouchers($fund, $vouchers, $type, $assert);

            sleep(1);
        }
    }

    /**
     * @param Fund $fund
     * @param array $vouchers
     * @param string $type
     * @param array $assert
     * @return void
     */
    protected function storeVouchers(
        Fund $fund,
        array $vouchers,
        string $type,
        array $assert
    ): void {
        $assertCreated = $assert['assert_creation'] === 'success';
        $errors = $assert['validation_errors'] ?? [];

        $organization = $fund->organization;
        $startDate = now();
        $headers = $this->makeApiHeaders($this->makeIdentityProxy($organization->identity));
        $data = compact('vouchers');
        $data['fund_id'] = $fund->id;

        // validate
        $validateResponse = $this->post(
            sprintf($this->apiUrl, $organization->id) . '/validate', $data, $headers
        );
        $uploadResponse = $this->post(sprintf($this->apiUrl, $organization->id), $data, $headers);

        if ($assertCreated) {
            $validateResponse->assertSuccessful();
            $uploadResponse->assertSuccessful();

            $vouchersBuilder = $this->getVouchersBuilder($fund, $startDate, $type);
            $this->checkVouchers($vouchersBuilder, $startDate, $vouchers, $assert);
        } else {
            $validateResponse->assertJsonValidationErrors($errors);
            $uploadResponse->assertJsonValidationErrors($errors);
        }
    }
}
