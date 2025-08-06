<?php

namespace Tests\Browser\Exports;

use App\Exports\ProductReservationsExport;
use App\Models\Fund;
use App\Models\Implementation;
use App\Models\ProductReservation;
use Exception;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\ExportTrait;
use Tests\Browser\Traits\HasFrontendActions;
use Tests\Browser\Traits\NavigatesFrontendDashboard;
use Tests\Browser\Traits\RollbackModelsTrait;
use Tests\DuskTestCase;
use Tests\Traits\MakesProductReservations;
use Tests\Traits\MakesTestFunds;
use Throwable;

class ProductReservationsExportTest extends DuskTestCase
{
    use ExportTrait;
    use MakesTestFunds;
    use HasFrontendActions;
    use RollbackModelsTrait;
    use MakesProductReservations;
    use NavigatesFrontendDashboard;

    /**
     * @throws Throwable
     * @return void
     */
    public function testProductReservationsExport(): void
    {
        $implementation = Implementation::byKey('nijmegen');

        $fund = $this->makeTestFund($implementation->organization);
        $reservation = $this->prepareData($fund);

        $organization = $reservation->product->organization;
        $this->assertNotNull($organization);

        $this->rollbackModels([], function () use ($implementation, $organization, $reservation) {
            $this->browse(function (Browser $browser) use ($implementation, $organization, $reservation) {
                $browser->visit($implementation->urlProviderDashboard());

                // Authorize identity
                $this->loginIdentity($browser, $organization->identity);
                $this->assertIdentityAuthenticatedOnProviderDashboard($browser, $organization->identity);
                $this->selectDashboardOrganization($browser, $organization);

                // Go to list, open export modal and assert all export fields in file
                $this->goToReservationsPage($browser);
                $this->searchTable($browser, '@tableReservation', $reservation->first_name, $reservation->id);

                $fields = array_pluck(ProductReservationsExport::getExportFields(), 'name');

                foreach (static::FORMATS as $format) {
                    // assert all fields exported
                    $this->openFilterDropdown($browser);
                    $data = $this->fillExportModalAndDownloadFile($browser, $format);
                    $data && $this->assertFields($reservation, $data, $fields);

                    // assert specific fields exported
                    $this->openFilterDropdown($browser);
                    $data = $this->fillExportModalAndDownloadFile($browser, $format, ['code']);

                    $data && $this->assertFields($reservation, $data, [
                        ProductReservationsExport::trans('code'),
                    ]);
                }

                // Logout
                $this->logout($browser);
            });
        }, function () use ($fund, $reservation) {
            $reservation && $reservation->delete();
            $fund && $this->deleteFund($fund);
        });
    }

    /**
     * @param Fund $fund
     * @throws Exception
     * @return ProductReservation
     */
    protected function prepareData(Fund $fund): ProductReservation
    {
        $this->makeProviderAndProducts($fund, 1);

        $voucher = $this->makeTestVoucher($fund, identity: $this->makeIdentity(), amount: 1000);
        $product = $this->findProductForReservation($voucher);

        $reservation = $this->makeReservation($voucher, $product, [
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
        ]);

        $this->assertNotNull($reservation);

        return $reservation;
    }

    /**
     * @param ProductReservation $reservation
     * @param array $rows
     * @param array $fields
     * @return void
     */
    protected function assertFields(
        ProductReservation $reservation,
        array $rows,
        array $fields
    ): void {
        // Assert that the first row (header) contains expected columns
        $this->assertEquals($fields, $rows[0]);
        $this->assertEquals($reservation->code, $rows[1][0]);
    }
}
