<?php

namespace Tests\Browser;

use App\Models\Fund;
use App\Models\FundProvider;
use App\Models\Identity;
use App\Models\Implementation;
use App\Models\Organization;
use App\Models\ProductReservation;
use App\Scopes\Builders\FundProviderQuery;
use App\Services\MailDatabaseLoggerService\Traits\AssertsSentEmails;
use Facebook\WebDriver\Exception\TimeOutException;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\WebDriverBy;
use Illuminate\Support\Arr;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\HasFrontendActions;
use Tests\DuskTestCase;
use Tests\Traits\MakesTestFundProviders;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestIdentities;
use Illuminate\Support\Facades\Cache;
use Tests\Traits\MakesProductReservations;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\Traits\MakesTestOrganizations;
use Tests\Traits\MakesTestProducts;

class ProductReservationTest extends DuskTestCase
{
    use WithFaker;
    use MakesTestFunds;
    use AssertsSentEmails;
    use MakesTestProducts;
    use HasFrontendActions;
    use MakesTestIdentities;
    use MakesTestOrganizations;
    use MakesTestFundProviders;
    use MakesProductReservations;

    protected ?Identity $identity;

    /**
     * @return void
     * @throws \Throwable
     */
    public function testWebshopProductReservationExample(): void
    {
        // Select implementation
        $implementation = Implementation::byKey('nijmegen');
        $organization = $implementation->organization;

        $fund = $this->makeTestFund($organization->fresh());

        // Models exist
        $this->assertNotNull($implementation);
        $this->assertNotNull($implementation->organization);

        $this->browse(function (Browser $browser) use ($implementation, $fund) {
            // Visit the url and wait for the page to load
            $browser->visit($implementation->urlWebshop());

            $identity = $this->makeIdentity($this->makeUniqueEmail());
            $provider = $this->makeTestProviderOrganization($this->makeIdentity($this->makeUniqueEmail()));

            $this->makeTestProductForReservation($provider);

            $implementation->funds->each(fn (Fund $fund) => $fund->makeVoucher($identity));
            $implementation->funds->each(fn (Fund $fund) => $this->makeTestFundProvider($provider, $fund));

            // Authorize identity
            $funds = $implementation->fresh()->funds
                ->filter(fn (Fund $model) => $model->id == $fund->id)
                ->filter(fn (Fund $fund) => FundProviderQuery::whereApprovedForFundsFilter(
                    FundProvider::query(),
                    $fund->id,
                )->exists());

            $this->loginIdentity($browser, $identity);
            $this->assertIdentityAuthenticatedOnWebshop($browser, $identity);
            $browser->waitFor('@headerTitle');

            // Assert at lease one fund exist
            $this->assertCount(1, $funds);
            $this->makeReservation($browser, $fund, $identity);

            // Logout user
            $this->logout($browser);
        });
    }

    /**
     * @return void
     * @throws \Throwable
     */
    public function testReservationState(): void
    {
        Cache::clear();

        $implementation = Implementation::general();
        $this->assertNotNull($implementation, 'Implementation not found.');

        $organization = Organization::where('name', 'Nijmegen')->first();

        $reservation = $this->makeBudgetReservationInDb($organization);
        $reservation = ProductReservation::find($reservation->id);

        $this->browse(function (Browser $browser) use ($implementation, $reservation) {
            $provider = $reservation->product->organization;
            $identity = $provider->identity;
            $this->assertNotNull($identity);

            $browser->visit($implementation->urlFrontend('provider'));

            // Authorize identity
            $this->loginIdentity($browser, $identity);
            $this->assertIdentityAuthenticatedOnProviderDashboard($browser, $identity);
            $this->selectDashboardOrganization($browser, $provider);

            $browser->waitFor('@asideMenuGroupSales');
            $browser->element('@asideMenuGroupSales')->click();
            $browser->waitFor('@reservationsPage');
            $browser->element('@reservationsPage')->click();
            $browser->waitFor('@reservationsTitle');

            $this->checkReservationState($browser, $reservation);

            if ($reservation->isPending()) {
                $reservation->acceptProvider();
                $reservation = ProductReservation::find($reservation->id);

                $this->assertSame(ProductReservation::STATE_ACCEPTED, $reservation->state);

                $browser->refresh();
                $this->checkReservationState($browser, $reservation);
            }

            if ($reservation->isAccepted()) {
                $reservation->rejectOrCancelProvider();
                $reservation = ProductReservation::find($reservation->id);

                $this->assertSame(ProductReservation::STATE_CANCELED_BY_PROVIDER, $reservation->state);

                $browser->refresh();
                $this->checkReservationState($browser, $reservation);
            }

            // Logout
            $this->logout($browser);
        });
    }

    /**
     * @param Browser $browser
     * @param ProductReservation $reservation
     * @return void
     * @throws TimeoutException
     */
    private function checkReservationState(Browser $browser, ProductReservation $reservation): void
    {
        $browser
            ->waitFor("@reservationRow$reservation->id")
            ->assertSeeIn("@reservationRow$reservation->id @reservationState", $reservation->state_locale);
    }

    /**
     * @param Browser $browser
     * @param Fund $fund
     * @param Identity $identity
     * @return void
     * @throws TimeOutException
     */
    private function makeReservation(
        Browser $browser,
        Fund $fund,
        Identity $identity,
    ): void {
        $startTime = now();

        $user = [
            'first_name' => 'John',
            'last_name' => 'Doe',
        ];

        $this->goToVouchersPage($browser, $identity);
        $this->goToVoucherPage($browser, $fund);

        // Find available product and open it
        $browser->waitFor('@productItem')->press('@productItem');
        $browser->waitFor('@fundItem');
        $productName = trim($browser->waitFor('@productName')->element('@productName')->getText());

        // Find available fund and reserve product
        $fundElement = $this->findFundReservationOptionElement($browser, $fund->name);
        $fundElement->findElement(WebDriverBy::xpath(".//*[@data-dusk='reserveProduct']"))->click();

        // Wait for the reservation modal and submit with no data
        $browser->waitFor('@modalProductReserve');
        if (!$identity->email) {
            $browser->within('@modalProductReserve', fn(Browser $el) => $el->click('@reserveSkipEmailStep'));
        }
        $browser->within('@modalProductReserve', fn(Browser $el) => $el->click('@btnSelectVoucher'));

        // Fill reservation fields (except notes)
        $browser->waitFor('@productReserveForm');
        $browser->within('@productReserveForm', function(Browser $browser) use ($user) {
            $browser->press('@btnSubmit');
            $browser->waitFor('.form-error');
            $browser->assertVisible('.form-error');

            // Fill form with data and submit again
            $browser->type('@productReserveFormFirstName', $user['first_name']);
            $browser->type('@productReserveFormLastName', $user['last_name']);
            $browser->press('@btnSubmit');
        });

        // Fill reservation notes
        $browser->waitFor('@productReserveNotes');
        $browser->within('@productReserveNotes', function(Browser $browser) {
            // Fill form with data and submit again
            $browser->type('@productReserveFormNote', $this->faker->text(100));
            $browser->press('@btnSubmit');
        });

        // Assert success
        $browser->waitForTextIn('@productReserveConfirmDetails', $user['first_name']);
        $browser->press('@btnConfirmSubmit');

        $browser->waitFor('@productReserveSuccess');
        $browser->within('@productReserveSuccess', fn(Browser $el) => $el->click('@btnReservationFinish'));

        // Assert redirected to reservations list
        $browser->waitFor('@reservationsTitle');

        // Assert reservation is created
        $reservation = ProductReservation::query()
            ->where($user)
            ->where('created_at', '>=', $startTime)
            ->whereRelation('voucher.identity', 'address', $identity->address)
            ->whereRelation('voucher.fund', 'name', $fund->name)
            ->whereRelation('product', 'name', $productName)
            ->first();

        $autoAccept = $reservation?->product->organization->reservations_auto_accept;
        $stateIsValid = $autoAccept ? $reservation->isAccepted() : $reservation->isPending();

        $this->assertNotNull($reservation, 'Reservation not created');
        $this->assertTrue($stateIsValid, 'Wrong reservation status');

        // find reserved product in list with pending label
        $this->assertReservationElementExists($browser, $reservation);

        // cancel reservation
        $browser->within("@reservationItem$reservation->id", fn(Browser $el) => $el->click('@btnCancelReservation'));

        $browser->waitFor('@modalProductReserveCancel');
        $browser->within('@modalProductReserveCancel', fn(Browser $el) => $el->click('@btnSubmit'));

        $browser->waitUntilMissingText($reservation->code);
        $browser->assertMissing("@reservationItem$reservation->id");

        $reservation->refresh();
        $this->assertTrue($reservation->isCanceledByClient(), 'Reservation not canceled.');
    }

    /**
     * @param Browser $browser
     * @param string $voucherTitle
     * @return RemoteWebElement|null
     * @throws TimeOutException
     */
    private function findVoucherElement(Browser $browser, string $voucherTitle): ?RemoteWebElement
    {
        $selector = '@voucherItem';

        $browser->waitFor($selector);

        foreach ($browser->elements($selector) as $element) {
            $text = $element->findElement(WebDriverBy::xpath(".//*[@data-dusk='voucherName']"))->getText();

            if (trim($text) === $voucherTitle) {
                return $element;
            }
        }

        return null;
    }

    /**
     * @param Browser $browser
     * @param string $fundTitle
     * @return RemoteWebElement|null
     * @throws TimeOutException
     */
    private function findFundReservationOptionElement(Browser $browser, string $fundTitle): ?RemoteWebElement
    {
        $selector = '@fundItem';

        $browser->waitFor($selector);

        $element = Arr::first($browser->elements($selector), function(RemoteWebElement $element) use ($fundTitle) {
            $fundNameElement = $element->findElement(WebDriverBy::xpath(".//*[@data-dusk='fundName']"));
            $fundNameText = $fundNameElement->getText();

            return trim($fundNameText) === $fundTitle;
        });

        $this->assertNotNull($element);

        return $element;
    }

    /**
     * @param Browser $browser
     * @param ProductReservation $reservation
     * @return void
     * @throws TimeoutException
     */
    private function assertReservationElementExists(
        Browser $browser,
        ProductReservation $reservation,
    ): void {
        $selector = "@reservationItem$reservation->id";
        $browser->waitFor($selector);

        $browser->within($selector, function(Browser $browser) use ($reservation) {
            $browser->assertVisible($reservation->isExpired() ? '@labelExpired' : [
                'pending' => '@labelPending',
                'accepted' => '@labelAccepted',
                'rejected' => '@labelRejected',
                'canceled' => '@labelCanceled',
                'canceled_by_client' => '@labelCanceled',
            ][$reservation->state]);

            $browser->assertSeeIn('@reservationProduct', $reservation->product->name);
            $browser->assertSeeIn('@reservationCode', $reservation->code);
        });
    }

    /**
     * @param Browser $browser
     * @param Identity $identity
     * @return void
     * @throws TimeOutException
     */
    private function goToVouchersPage(Browser $browser, Identity $identity): void
    {
        $this->assertIdentityAuthenticatedOnWebshop($browser, $identity);

        $browser->waitFor('@userVouchers');
        $browser->element('@userVouchers')->click();
    }

    /**
     * @param Browser $browser
     * @param Fund $fund
     * @return void
     * @throws TimeOutException
     */
    private function goToVoucherPage(Browser $browser, Fund $fund): void
    {
        // find voucher and open it
        $voucherElement = $this->findVoucherElement($browser, $fund->name);
        $this->assertNotNull($voucherElement, "Voucher for '$fund->name' not found!");

        $voucherElement->click();

        $browser->waitFor('@voucherTitle');
        $browser->assertSeeIn('@voucherTitle', $fund->name);
    }
}
