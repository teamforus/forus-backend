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
use Tests\DuskTestCase;
use Tests\Traits\MakesTestIdentities;
use Illuminate\Support\Facades\Cache;
use Tests\Traits\MakesProductReservations;

class ProductReservationTest extends DuskTestCase
{
    use AssertsSentEmails, MakesTestIdentities, MakesProductReservations;

    protected ?Identity $identity;

    /**
     * @return void
     * @throws \Throwable
     */
    public function testWebshopProductReservationExample(): void
    {
        // Select implementation
        $implementation = Implementation::byKey('nijmegen');

        // Models exist
        $this->assertNotNull($implementation);
        $this->assertNotNull($implementation->organization);

        $this->browse(function (Browser $browser) use ($implementation) {
            // Visit the url and wait for the page to load
            $browser->visit($implementation->urlWebshop());

            // Authorize identity
            $funds = $implementation->funds->filter(function(Fund $fund) {
                 return FundProviderQuery::whereApprovedForFundsFilter(FundProvider::query(), $fund->id)->exists();
            });

            $identity = $this->makeIdentity($this->makeUniqueEmail());
            $proxy = $this->makeIdentityProxy($identity);
            $identity->primary_email->setVerified();
            $funds->each(fn (Fund $fund) => $fund->makeVoucher($identity->address));

            $browser->script("localStorage.setItem('active_account', '$proxy->access_token')");
            $browser->refresh();
            $browser->waitFor('@headerTitle');

            // Assert at lease one fund exist
            $this->assertGreaterThan(1, $implementation->funds->count());

            // Make reservations on all funds
            $funds->each(fn (Fund $fund) => $this->makeReservation($browser, $fund, $identity));

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
            $proxy = $this->makeIdentityProxy($identity);
            $browser->script("localStorage.setItem('active_account', '$proxy->access_token')");
            $browser->refresh();

            $browser->waitFor('@providerOverview');
            $this->switchToOrganization($browser, $provider, $identity);

            $browser->waitFor('@providerOverview', 10);
            $browser->element('@reservationsPage')->click();
            $browser->waitFor('@reservationsTitle');

            $this->checkReservationState($browser, $reservation);

            if ($reservation->isPending()) {
                $reservation->acceptProvider();
                $reservation = ProductReservation::find($reservation->id);

                $this->assertTrue($reservation->state === ProductReservation::STATE_ACCEPTED);

                $browser->refresh();
                $this->checkReservationState($browser, $reservation);
            }

            if ($reservation->isAccepted()) {
                $reservation->rejectOrCancelProvider();
                $reservation = ProductReservation::find($reservation->id);

                $this->assertTrue($reservation->state === ProductReservation::STATE_CANCELED_BY_PROVIDER);

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
     * @throws TimeOutException
     */
    private function checkReservationState(
        Browser $browser,
        ProductReservation $reservation
    ): void {
        $browser->waitFor('@reservationRow' . $reservation->id);
        $browser->within('@reservationRow' . $reservation->id, function(Browser $browser) use ($reservation) {
            $browser->assertSeeIn('@reservationState', $reservation->state_locale);
        });
    }

    /**
     * @param Browser $browser
     * @param Organization $organization
     * @param Identity $identity
     * @return void
     * @throws TimeOutException
     */
    private function switchToOrganization(
        Browser $browser,
        Organization $organization,
        Identity $identity
    ): void {
        $browser->waitFor('@identityEmail');
        $browser->assertSeeIn('@identityEmail', $identity->email);
        $browser->waitFor('@headerOrganizationSwitcher');
        $browser->press('@headerOrganizationSwitcher');
        $browser->waitFor("@headerOrganizationItem$organization->id");
        $browser->press("@headerOrganizationItem$organization->id");
        $browser->pause(5000);
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
        $fundElement->findElement(WebDriverBy::xpath(".//*[@dusk='reserveProduct']"))->click();

        // Wait for the reservation modal and submit with no data
        $browser->waitFor('@modalProductReserve');
        $browser->within('@modalProductReserve', fn(Browser $el) => $el->click('@btnSubmit'));

        // Assert validation errors
        $browser->waitFor('@modalProductReserveForm');
        $browser->within('@modalProductReserveForm', function(Browser $browser) use ($user) {
            $browser->press('@btnSubmit');
            $browser->waitFor('.form-error');
            $browser->assertVisible('.form-error');

            // Fill form with data and submit again
            $browser->type('@productReserveFormFirstName', $user['first_name']);
            $browser->type('@productReserveFormLastName', $user['last_name']);
            $browser->press('@btnSubmit');

            // Assert success
            $browser->waitForTextIn('@productReserveConfirmDetails', $user['first_name']);
            $browser->press('@btnConfirmSubmit');
        });

        // Assert redirected to reservations list
        $browser->waitFor('@reservationsTitle');

        // Assert reservation is created
        $reservation = ProductReservation::query()
            ->where($user)
            ->where('created_at', '>=', $startTime)
            ->whereRelation('voucher.identity', 'address', $identity->address)
            ->whereRelation('voucher', 'fund_id', $fund->id)
            ->whereRelation('product', 'name', $productName)
            ->first();

        $autoAccept = $reservation?->product->organization->reservations_auto_accept;
        $stateIsValid = $autoAccept ? $reservation->isAccepted() : $reservation->isPending();

        $this->assertNotNull($reservation, 'Reservation not created');
        $this->assertTrue($stateIsValid, 'Wrong reservation status');

        // find reserved product in list with pending label
        $reservationElement = $this->findReservationElement($browser, $reservation);
        $this->assertNotNull($reservationElement, 'Reservation not created');

        // cancel reservation
        $reservationElement->findElement(WebDriverBy::xpath(".//*[@dusk='btnCancelReservation']"))->click();

        $browser->waitFor('@modalProductReserveCancel');
        $browser->within('@modalProductReserveCancel', fn(Browser $el) => $el->click('@btnSubmit'));
        $browser->waitUntilMissingText($reservation->code);

        $reservationElement = $this->findReservationElement($browser, $reservation);
        $this->assertNull($reservationElement, 'Reservation not deleted.');

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
            $text = $element->findElement(WebDriverBy::xpath(".//*[@dusk='voucherName']"))->getText();

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
            $fundNameElement = $element->findElement(WebDriverBy::xpath(".//*[@dusk='fundName']"));
            $fundNameText = $fundNameElement->getText();

            return trim($fundNameText) === $fundTitle;
        });

        $this->assertNotNull($element);

        return $element;
    }

    /**
     * @param Browser $browser
     * @param ProductReservation $reservation
     * @return RemoteWebElement|null
     */
    private function findReservationElement(
        Browser $browser,
        ProductReservation $reservation,
    ): ?RemoteWebElement {
        $selector = "@reservationItem$reservation->id";

        try {
            $browser->waitFor($selector);
        } catch (TimeOutException) {
            return null;
        }

        $browser->within($selector, function(Browser $browser) use ($reservation) {
            $browser->assertVisible($reservation->hasExpired() ? '@labelExpired' : [
                'pending' => '@labelPending',
                'accepted' => '@labelAccepted',
                'rejected' => '@labelRejected',
                'canceled' => '@labelCanceled',
            ][$reservation->state]);

            $browser->assertSeeIn('@reservationProduct', $reservation->product->name);
            $browser->assertSeeIn('@reservationCode', $reservation->code);
        });

        return $browser->element($selector);
    }

    /**
     * @param Browser $browser
     * @param Identity $identity
     * @return void
     * @throws TimeOutException
     */
    private function goToVouchersPage(Browser $browser, Identity $identity): void
    {
        $browser->waitFor('@identityEmail');
        $browser->assertSeeIn('@identityEmail', $identity->email);

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

    /**
     * @param Browser $browser
     * @return void
     * @throws TimeOutException
     */
    private function logout(Browser $browser): void
    {
        $browser->waitFor('@userProfile');
        $browser->element('@userProfile')->click();
        $browser->waitFor('@btnUserLogout');
        $browser->element('@btnUserLogout')->click();
    }
}
