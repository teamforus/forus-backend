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
use Tests\Browser\Traits\HasFrontendActions;
use Tests\Browser\Traits\RollbackModelsTrait;
use Tests\DuskTestCase;
use Tests\Traits\MakesProductReservations;
use Tests\Traits\MakesTestFunds;
use Throwable;

class ProductReservationsSearchFilterTest extends DuskTestCase
{
    use MakesTestFunds;
    use HasFrontendActions;
    use RollbackModelsTrait;
    use MakesProductReservations;

    protected const array STATES = [
        'pending' => 'In afwachting',
        'accepted' => 'Geaccepteerd',
        'rejected' => 'Geweigerd',
        'canceled' => 'Geannuleerd',
    ];

    /**
     * @throws Throwable
     * @return void
     */
    public function testProductReservationsFilters(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity($this->makeUniqueEmail()));
        $implementation = Implementation::byKey('nijmegen');
        $identity = $organization->identity;

        $fundConfigsData = [
            'implementation_id' => $implementation->id,
        ];

        $fund = $this->makeTestFund($organization, fundData: [
            'description_text' => $this->faker->sentence,
            'description_short' => $this->faker->sentence,
        ], fundConfigsData: $fundConfigsData);

        $pendingReservation = $this->makeReservationForFund($fund);

        $fund2 = $this->makeTestFund($organization, fundData: [
            'description_text' => $this->faker->sentence,
            'description_short' => $this->faker->sentence,
        ], fundConfigsData: $fundConfigsData);

        $acceptedReservation = $this->makeReservationForFund($fund2)->acceptProvider();

        $this->rollbackModels([], function () use (
            $implementation,
            $identity,
            $fund,
            $pendingReservation,
            $acceptedReservation
        ) {
            $this->browse(function (Browser $browser) use (
                $implementation,
                $identity,
                $fund,
                $pendingReservation,
                $acceptedReservation
            ) {
                $browser->visit($implementation->urlWebshop());

                $this->loginIdentity($browser, $identity);
                $this->assertIdentityAuthenticatedOnWebshop($browser, $identity);
                $this->goToIdentityReservations($browser);

                $this->assertReservationsSearchIsWorking($browser, $pendingReservation)
                    ->fillSearchForEmptyResults($browser);

                $this->assertReservationsFilterByProvider($browser, $pendingReservation)
                    ->clearProviderSelect($browser)
                    ->fillSearchForEmptyResults($browser);

                $this->assertReservationsFilterByFund($browser, $pendingReservation, $fund)
                    ->clearFundSelect($browser)
                    ->fillSearchForEmptyResults($browser);

                $this
                    ->assertReservationsFilterByState($browser, $pendingReservation, $pendingReservation::STATE_PENDING)
                    ->fillSearchForEmptyResults($browser)
                    ->assertReservationsFilterByState($browser, $acceptedReservation, $acceptedReservation::STATE_ACCEPTED)
                    ->fillSearchForEmptyResults($browser)
                    ->assertReservationsFilterByState($browser, $pendingReservation->rejectOrCancelProvider(), $pendingReservation::STATE_REJECTED);

                $this->logout($browser);
            });
        }, function () use ($organization) {
            $organization->funds->each(fn (Fund $fund) => $this->deleteFund($fund));
        });
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testProductReservationsFilterByActiveTabs(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity($this->makeUniqueEmail()));
        $implementation = Implementation::byKey('nijmegen');
        $identity = $organization->identity;

        $fundConfigsData = [
            'implementation_id' => $implementation->id,
        ];

        $fund = $this->makeTestFund($organization, fundConfigsData: $fundConfigsData);
        $acceptedReservation = $this->makeReservationForFund($fund)->acceptProvider();

        $fund2 = $this->makeTestFund($organization, fundConfigsData: $fundConfigsData);
        $canceledReservation = $this->makeReservationForFund($fund2)->acceptProvider()->rejectOrCancelProvider();

        $this->rollbackModels([], function () use (
            $implementation,
            $identity,
            $acceptedReservation,
            $canceledReservation
        ) {
            $this->browse(function (Browser $browser) use (
                $implementation,
                $identity,
                $acceptedReservation,
                $canceledReservation
            ) {
                $browser->visit($implementation->urlWebshop());

                $this->loginIdentity($browser, $identity);
                $this->assertIdentityAuthenticatedOnWebshop($browser, $identity);
                $this->goToIdentityReservations($browser);

                $this->assertReservationsFilterByActiveTabs($browser, $acceptedReservation, $canceledReservation);

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

        $provider = $this->makeTestProviderOrganization($this->makeIdentity($this->makeUniqueEmail()));
        $product = $this->createProductForReservation($provider, [$fund]);

        $reservation = $this->makeReservation($voucher, $product, [
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
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

        $this
            ->assertReservationVisible($browser, $activeReservation)
            ->assertReservationNotVisible($browser, $inactiveReservation);

        $browser->click('@reservationsFilterArchived');

        $this
            ->assertReservationVisible($browser, $inactiveReservation)
            ->assertReservationNotVisible($browser, $activeReservation);
    }

    /**
     * @param Browser $browser
     * @param ProductReservation $reservation
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeoutException
     * @return ProductReservationsSearchFilterTest
     */
    protected function assertReservationsFilterByProvider(
        Browser $browser,
        ProductReservation $reservation
    ): static {
        $browser->waitFor('@selectControlProviders');
        $browser->click('@selectControlProviders .select-control-search');
        $this->findOptionElement($browser, '@selectControlProviders', $reservation->product->organization->name)->click();

        $this->assertReservationVisible($browser, $reservation);

        return $this;
    }

    /**
     * @param Browser $browser
     * @param ProductReservation $reservation
     * @param Fund $fund
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeoutException
     * @return ProductReservationsSearchFilterTest
     */
    protected function assertReservationsFilterByFund(
        Browser $browser,
        ProductReservation $reservation,
        Fund $fund,
    ): static {
        $browser->waitFor('@selectControlFunds');
        $browser->click('@selectControlFunds .select-control-search');
        $this->findOptionElement($browser, '@selectControlFunds', $fund->name)->click();

        $this->assertReservationVisible($browser, $reservation);

        return $this;
    }

    /**
     * @param Browser $browser
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeoutException
     * @return ProductReservationsSearchFilterTest
     */
    protected function clearFundSelect(Browser $browser): static
    {
        $browser->waitFor('@selectControlFunds');
        $browser->click('@selectControlFunds .select-control-search');
        $this->findOptionElement($browser, '@selectControlFunds', 'Alle tegoeden...')->click();

        return $this;
    }

    /**
     * @param Browser $browser
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeoutException
     * @return ProductReservationsSearchFilterTest
     */
    protected function clearProviderSelect(Browser $browser): static
    {
        $browser->waitFor('@selectControlProviders');
        $browser->click('@selectControlProviders .select-control-search');
        $this->findOptionElement($browser, '@selectControlProviders', 'Selecteer aanbieder...')->click();

        return $this;
    }

    /**
     * @param Browser $browser
     * @param ProductReservation $reservation
     * @param string $state
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeOutException
     * @return ProductReservationsSearchFilterTest
     */
    protected function assertReservationsFilterByState(
        Browser $browser,
        ProductReservation $reservation,
        string $state,
    ): static {
        $browser->waitFor('@selectControlStates');
        $browser->click('@selectControlStates .select-control-search');
        $this->findOptionElement($browser, '@selectControlStates', self::STATES[$state])->click();

        $this->assertReservationVisible($browser, $reservation);

        return $this;
    }

    /**
     * @param Browser $browser
     * @param ProductReservation $reservation
     * @throws TimeOutException
     * @return ProductReservationsSearchFilterTest
     */
    protected function assertReservationsSearchIsWorking(Browser $browser, ProductReservation $reservation): static
    {
        $this->searchWebshopList($browser, '@listReservations', $reservation->code, $reservation->id);
        $this->clearField($browser, '@listReservationsSearch');

        $this->fillSearchForEmptyResults($browser);

        $this->searchWebshopList($browser, '@listReservations', $reservation->first_name, $reservation->id);
        $this->clearField($browser, '@listReservationsSearch');

        $this->fillSearchForEmptyResults($browser);

        $this->searchWebshopList($browser, '@listReservations', $reservation->last_name, $reservation->id);
        $this->clearField($browser, '@listReservationsSearch');

        $this->fillSearchForEmptyResults($browser);

        $this->searchWebshopList($browser, '@listReservations', $reservation->voucher->identity->email, $reservation->id, 2);
        $this->clearField($browser, '@listReservationsSearch');

        $this->fillSearchForEmptyResults($browser);

        $this->searchWebshopList($browser, '@listReservations', $reservation->product->name, $reservation->id);
        $this->clearField($browser, '@listReservationsSearch');

        $this->fillSearchForEmptyResults($browser);

        $this->searchWebshopList($browser, '@listReservations', $reservation->product->description, $reservation->id);
        $this->clearField($browser, '@listReservationsSearch');

        $this->fillSearchForEmptyResults($browser);

        $this->searchWebshopList($browser, '@listReservations', $reservation->voucher->fund->name, $reservation->id);
        $this->clearField($browser, '@listReservationsSearch');

        $this->fillSearchForEmptyResults($browser);

        $this->searchWebshopList($browser, '@listReservations', $reservation->voucher->fund->description_text, $reservation->id);
        $this->clearField($browser, '@listReservationsSearch');

        $this->fillSearchForEmptyResults($browser);

        $this->searchWebshopList($browser, '@listReservations', $reservation->voucher->fund->description_short, $reservation->id);
        $this->clearField($browser, '@listReservationsSearch');

        $this->fillSearchForEmptyResults($browser);

        $this->searchWebshopList($browser, '@listReservations', $reservation->voucher->fund->organization->name, $reservation->id, 2);
        $this->clearField($browser, '@listReservationsSearch');

        return $this;
    }

    /**
     * @param Browser $browser
     * @throws TimeoutException
     * @return ProductReservationsSearchFilterTest
     */
    protected function fillSearchForEmptyResults(Browser $browser): static
    {
        $this->searchWebshopList($browser, '@listReservations', '###############', null, 0);
        $this->clearField($browser, '@listReservationsSearch');

        return $this;
    }

    /**
     * @param Browser $browser
     * @param ProductReservation $reservation
     * @throws TimeoutException
     * @return ProductReservationsSearchFilterTest
     */
    protected function assertReservationVisible(Browser $browser, ProductReservation $reservation): static
    {
        $browser->waitFor("@listReservationsRow$reservation->id");
        $browser->assertVisible("@listReservationsRow$reservation->id");
        $this->assertWebshopRowsCount($browser, 1, '@listReservationsContent');

        return $this;
    }

    /**
     * @param Browser $browser
     * @param ProductReservation $reservation
     * @throws TimeoutException
     * @return ProductReservationsSearchFilterTest
     */
    protected function assertReservationNotVisible(Browser $browser, ProductReservation $reservation): static
    {
        $browser->waitUntilMissing("@listReservationsRow$reservation->id");

        return $this;
    }
}
