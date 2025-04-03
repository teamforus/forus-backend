<?php

namespace Tests\Browser\Exports;

use App\Exports\ProductReservationsExport;
use App\Models\Fund;
use App\Models\Implementation;
use App\Models\ProductReservation;
use Tests\Browser\Traits\ExportTrait;
use Facebook\WebDriver\Exception\TimeOutException;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\HasFrontendActions;
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
                $this->goToListPage($browser);
                $this->searchProductReservation($browser, $reservation);

                $browser->waitFor('@showFilters');
                $browser->element('@showFilters')->click();

                $this->fillExportModal($browser);
                $csvData = $this->parseCsvFile();

                $fields = array_pluck(ProductReservationsExport::getExportFields(), 'name');
                $this->assertFields($reservation, $csvData, $fields);

                // Open export modal, select specific fields and assert it
                $browser->waitFor('@showFilters');
                $browser->element('@showFilters')->click();

                $this->fillExportModal($browser, ['code']);
                $csvData = $this->parseCsvFile();

                $this->assertFields($reservation, $csvData, [
                    ProductReservationsExport::trans('code'),
                ]);

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
     * @return ProductReservation
     * @throws \Exception
     */
    protected function prepareData(Fund $fund): ProductReservation
    {
        $this->makeProviderAndProducts($fund, 1);

        $voucher = $this->findVoucherForReservation($fund->organization, Fund::TYPE_BUDGET);
        $product = $this->findProductForReservation($voucher);

        $reservation = $this->makeReservation($voucher, $product, [
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
        ]);

        $this->assertNotNull($reservation);

        return $reservation;
    }

    /**
     * @param Browser $browser
     * @return void
     * @throws TimeoutException
     */
    protected function goToListPage(Browser $browser): void
    {
        $browser->waitFor('@asideMenuGroupSales');
        $browser->element('@asideMenuGroupSales')->click();
        $browser->waitFor('@reservationsPage');
        $browser->element('@reservationsPage')->click();
    }

    /**
     * @param Browser $browser
     * @param ProductReservation $reservation
     * @return void
     * @throws TimeoutException
     */
    protected function searchProductReservation(Browser $browser, ProductReservation $reservation): void
    {
        $browser->waitFor('@searchReservations');
        $browser->type('@searchReservations', $reservation->first_name);

        $browser->waitFor("@reservationRow$reservation->id", 20);
        $browser->assertVisible("@reservationRow$reservation->id");

        $browser->waitUntil("document.querySelectorAll('#reservationsTable tbody tr').length === 1");
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
