<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Tests\Traits\MakesAssertStoreUploadedCsvFile;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Throwable;

class ProductReservationsUploadBatchTest extends TestCase
{
    use WithFaker;
    use MakesTestFunds;
    use DatabaseTransactions;
    use MakesTestOrganizations;
    use MakesAssertStoreUploadedCsvFile;

    /**
     * @throws Throwable
     * @return void
     */
    public function testProductReservationsBatchStoreUploadedCsvFile(): void
    {
        $sponsor = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($sponsor, fundConfigsData: ['allow_physical_cards' => true]);

        $provider = $this->makeTestProviderOrganization($this->makeIdentity(), ['allow_batch_reservations' => true]);
        $product = $this->makeTestProducts($provider)[0];
        $this->addProductToFund($fund, $product, false);

        $data = [
            'number' => random_int(1000000000000000, 9999999999999999),
            'product_id' => $product->id,
        ];

        $voucher = $fund->makeVoucher($this->makeIdentity());
        $voucher->addPhysicalCard($data['number']);

        $this->apiMakeReservationRequestBatchRequest($provider, [$data], true)->assertSuccessful();

        $employee = $provider->findEmployee($provider->identity);
        $log = $this->assertLogCreated($employee, $employee::EVENT_UPLOADED_RESERVATIONS, 1);

        $this->assertLoggedUploadedFileContent($log, [$data]);
    }
}
