<?php

namespace Tests\Browser\Filters\Webshop;

use App\Models\Fund;
use App\Models\Implementation;
use App\Models\ProductReservation;
use App\Models\Voucher;
use Exception;
use Facebook\WebDriver\Exception\ElementClickInterceptedException;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\TimeOutException;
use Laravel\Dusk\Browser;
use Throwable;

class ProductReservationsWebshopSearchFilterTest extends BaseWebshopSearchFilter
{
    protected const array STATES = [
        'pending' => 'In afwachting',
        'accepted' => 'Geaccepteerd',
        'rejected' => 'Geweigerd',
        'canceled' => 'Geannuleerd',
    ];

    /**
     * @return string
     */
    public function getListSelector(): string
    {
        return '@listReservations';
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testProductReservationsFilters(): void
    {
        $this->assertReservationFilters(function (Browser $browser, ProductReservation $request1, ProductReservation $request2) {
            $pendingReservation = $request1;
            $acceptedReservation = $request2->acceptProvider();

            $this->fillListSearchForEmptyResults($browser);
            $this->assertReservationsSearchIsWorking($browser, $pendingReservation);

            $this->fillListSearchForEmptyResults($browser);
            $this->assertListFilterByOrganization($browser, $pendingReservation->product->organization, $pendingReservation->id, 1);

            $this->fillListSearchForEmptyResults($browser);
            $this->assertListFilterByFund($browser, $pendingReservation->voucher->fund, $pendingReservation->id, 1);

            $this->fillListSearchForEmptyResults($browser);
            $this->assertListFilterByState($browser, self::STATES[$pendingReservation::STATE_PENDING], $pendingReservation->id, 1);

            $this->fillListSearchForEmptyResults($browser);
            $this->assertListFilterByState($browser, self::STATES[$acceptedReservation::STATE_ACCEPTED], $acceptedReservation->id, 1);

            $pendingReservation->rejectOrCancelProvider();

            $this->fillListSearchForEmptyResults($browser);
            $this->assertListFilterByState($browser, self::STATES[$pendingReservation::STATE_REJECTED], $pendingReservation->refresh()->id, 1);
        });
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testProductReservationsFilterByActiveTabs(): void
    {
        $this->assertReservationFilters(function (Browser $browser, ProductReservation $request1, ProductReservation $request2) {
            $request1->acceptProvider();
            $request2->acceptProvider()->rejectOrCancelProvider();

            $this->assertReservationsFilterByActiveTabs($browser, $request1, $request2);
        });
    }

    /**
     * @param callable $callback
     * @throws Throwable
     * @return void
     */
    protected function assertReservationFilters(callable $callback): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity($this->makeUniqueEmail()));
        $implementation = Implementation::byKey('nijmegen');
        $identity = $organization->identity;

        $fund = $this->makeTestFund($organization, implementation: $implementation);
        $fund2 = $this->makeTestFund($organization, implementation: $implementation);

        $reservation1 = $this->makeReservationForFund($fund);
        $reservation2 = $this->makeReservationForFund($fund2);

        $this->rollbackModels([], function () use ($implementation, $identity, $callback, $reservation1, $reservation2) {
            $this->browse(function (Browser $browser) use ($implementation, $identity, $callback, $reservation1, $reservation2) {
                $browser->visit($implementation->urlWebshop());

                $this->loginIdentity($browser, $identity);
                $this->assertIdentityAuthenticatedOnWebshop($browser, $identity);
                $this->goToIdentityReservations($browser);

                $callback($browser, $reservation1, $reservation2);
                $this->logout($browser);
            });
        }, function () use ($organization) {
            $organization->funds->each(fn (Fund $fund) => $this->deleteFund($fund));
        });
    }

    /**
     * @param Fund $fund
     * @throws Exception
     * @return ProductReservation
     */
    protected function makeReservationForFund(Fund $fund): ProductReservation
    {
        $this->makeProviderAndProducts($fund, 1);

        $voucher = $fund->makeVoucher($fund->organization->identity, [
            'state' => Voucher::STATE_ACTIVE,
        ], 10000);

        $provider = $this->makeTestProviderOrganization($this->makeIdentity());
        $product = $this->createProductForReservation($provider, [$fund]);

        $reservation = $this->makeReservation($voucher, $product, [
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
        ]);

        $this->assertNotNull($reservation);

        return $reservation;
    }

    /**
     * @param Browser $browser
     * @param ProductReservation $activeReservation
     * @param ProductReservation $inactiveReservation
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeOutException
     * @return void
     */
    protected function assertReservationsFilterByActiveTabs(
        Browser $browser,
        ProductReservation $activeReservation,
        ProductReservation $inactiveReservation
    ): void {
        $browser->waitFor('@reservationsFilterActive');
        $browser->click('@reservationsFilterActive');
        $this->assertListVisibility($browser, $activeReservation->id, true);

        $browser->click('@reservationsFilterArchived');
        $this->assertListVisibility($browser, $inactiveReservation->id, true);
    }

    /**
     * @param Browser $browser
     * @param ProductReservation $reservation
     * @throws TimeOutException
     * @return void
     */
    protected function assertReservationsSearchIsWorking(Browser $browser, ProductReservation $reservation): void
    {
        $this->assertListFilterQueryValue($browser, $reservation->code, $reservation->id);
        $this->assertListFilterQueryValue($browser, $reservation->first_name, $reservation->id);
        $this->assertListFilterQueryValue($browser, $reservation->last_name, $reservation->id);
        $this->assertListFilterQueryValue($browser, $reservation->voucher->identity->email, $reservation->id, 2);

        $this->assertListFilterQueryValue($browser, $reservation->product->name, $reservation->id);
        $this->assertListFilterQueryValue($browser, $reservation->product->description, $reservation->id);

        $this->assertListFilterQueryValue($browser, $reservation->voucher->fund->name, $reservation->id);
        $this->assertListFilterQueryValue($browser, $reservation->voucher->fund->description_text, $reservation->id);
        $this->assertListFilterQueryValue($browser, $reservation->voucher->fund->description_short, $reservation->id);
        $this->assertListFilterQueryValue($browser, $reservation->voucher->fund->organization->name, $reservation->id, 2);
    }
}
