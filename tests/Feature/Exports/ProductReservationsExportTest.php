<?php

namespace Tests\Feature\Exports;

use App\Exports\ProductReservationsExport;
use App\Models\Fund;
use App\Models\ProductReservation;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;
use Tests\Traits\BaseExport;
use Tests\Traits\MakesProductReservations;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Throwable;

class ProductReservationsExportTest extends TestCase
{
    use BaseExport;
    use MakesTestFunds;
    use DatabaseTransactions;
    use MakesTestOrganizations;
    use MakesProductReservations;

    /**
     * @var string
     */
    protected string $apiExportUrl = '/api/v1/platform/organizations/%s/product-reservations/export';

    /**
     * @throws Throwable
     * @return void
     */
    public function testProductReservationsExport(): void
    {
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $sponsorOrganization = $this->makeTestOrganization($identity);
        $fund = $this->makeTestFund($sponsorOrganization);
        $this->makeProviderAndProducts($fund, 1);

        $voucher = $this->findVoucherForReservation($sponsorOrganization, Fund::TYPE_BUDGET);
        $product = $this->findProductForReservation($voucher);

        $reservation = $this->makeReservation($voucher, $product);
        $organization = $product->organization;

        $apiHeaders = $this->makeApiHeaders($this->makeIdentityProxy($organization->identity));

        // Assert export without fields - must be all fields by default
        $response = $this->get(
            sprintf($this->apiExportUrl, $organization->id) . "?data_format=csv",
            $apiHeaders
        );

        $fields = array_pluck(ProductReservationsExport::getExportFields(), 'name');
        $this->assertFields($response, $reservation, $fields);

        // Assert with passed all fields
        $url = sprintf($this->apiExportUrl, $organization->id) . '?' . http_build_query([
                'data_format' => 'csv',
                'fields' => ProductReservationsExport::getExportFieldsRaw(),
            ]);

        $response = $this->get($url, $apiHeaders);
        $this->assertFields($response, $reservation, $fields);

        // Assert specific fields
        $url = sprintf($this->apiExportUrl, $organization->id) . '?' . http_build_query([
                'data_format' => 'csv',
                'fields' => ['code'],
            ]);

        $response = $this->get($url, $apiHeaders);

        $this->assertFields($response, $reservation, [
            ProductReservationsExport::trans('code'),
        ]);
    }

    /**
     * @param TestResponse $response
     * @param ProductReservation $reservation
     * @param array $fields
     * @return void
     */
    protected function assertFields(
        TestResponse $response,
        ProductReservation $reservation,
        array $fields,
    ): void {
        $response->assertStatus(200);
        $response->assertDownload();

        $rows = $this->getCsvData($response);

        // Assert that the first row (header) contains expected columns
        $this->assertEquals($fields, $rows[0]);

        // Assert specific fields
        $this->assertEquals($reservation->code, $rows[1][0]);
    }
}
