<?php

namespace Tests\Browser;

use App\Models\Identity;
use App\Models\Implementation;
use App\Models\Organization;
use App\Models\ProductReservation;
use App\Services\MailDatabaseLoggerService\Traits\AssertsSentEmails;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\TimeOutException;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\WebDriverBy;
use Illuminate\Support\Str;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ProductReservationTest extends DuskTestCase
{
    use AssertsSentEmails;

    protected ?Identity $identity;

    /**
     * @return void
     * @throws \Throwable
     */
    public function testIdentityEmailDashboardExample(): void
    {
        $implementation = Implementation::where('key', 'general')->first();
        $organization = Organization::where('name', 'Stadjerspas')->first();

        // Models exist
        $this->assertNotNull($implementation);
        $this->assertNotNull($organization);

        $this->identity = $organization->identity;
        $link = $implementation->urlWebshop();
        $fundSubsidyTitle = 'Stadjerspas';
        $fundBudgetTitle = 'Stadjerspas II';
        $user = [
            'first_name' => 'John',
            'last_name' => 'Doe',
        ];

        $this->browse(function (Browser $browser) use ($link, $user, $fundSubsidyTitle, $fundBudgetTitle) {
            // Visit the url and wait for the page to load
            $browser->visit($link);

            // Authorize identity
            $proxy = $this->makeIdentityProxy($this->identity);
            $browser->script("localStorage.setItem('active_account', '$proxy->access_token')");

            $browser->refresh();

            $browser->waitFor('@headerTitle');

            $this->makeReservation($browser, $user, $fundSubsidyTitle);
            $this->makeReservation($browser, $user, $fundBudgetTitle);

            $this->logout($browser);
        });
    }

    /**
     * @param Browser $browser
     * @param array $user
     * @param string $fundTitle
     * @return void
     * @throws TimeOutException
     */
    private function makeReservation(Browser $browser, array $user, string $fundTitle): void
    {
        $startTime = now();

        $this->goToVouchersPage($browser);

        // find voucher and open it
        $voucherElement = $this->findVoucherElement($browser, $fundTitle);
        $this->assertNotNull($voucherElement);

        $voucherElement->click();

        $browser->waitFor('@voucherTitle');
        $browser->assertSeeIn('@voucherTitle', $fundTitle);

        // find available product and open it
        $browser->waitFor('@productItem');
        $productElement = $browser->element('@productItem');
        $productName = $productElement->findElement(WebDriverBy::xpath(".//*[@dusk='productName']"))->getText();
        $productElement->click();

        // find available fund and reserve product
        $fundElement = $this->findFundElement($browser, $fundTitle);
        $this->assertNotNull($fundElement);

        $fundElement->findElement(WebDriverBy::xpath(".//*[@dusk='reserveProduct']"))->click();

        $browser->waitFor('@modalProductReserve');
        $browser->within('@modalProductReserve', function(Browser $browser) {
            $browser->click('@btnSubmit');
        });

        $browser->waitFor('@modalProductReserveForm');
        $browser->within('@modalProductReserveForm', function(Browser $browser) use ($user) {
            $browser->press('@btnSubmit');
            $browser->waitFor('.form-error');
            $browser->assertVisible('.form-error');

            $browser->type('@productReserveFormFirstName', $user['first_name']);
            $browser->type('@productReserveFormLastName', $user['last_name']);
            $browser->press('@btnSubmit');

            $browser->waitForTextIn('@productReserveConfirmDetails', 'John');

            $browser->press('@btnConfirmSubmit');
        });

        $browser->waitFor('@reservationsTitle');

        $reservation = ProductReservation::where($user)
            ->where('created_at', '>=', $startTime)
            ->first();

        $this->assertNotNull($reservation);
        $this->assertEquals($reservation->product->name, $productName);

        // find reserved product in list with pending label
        $reservationElement = $this->findReservationElement($browser, $productName, $reservation->code);
        $this->assertNotNull($reservationElement);

        // cancel reservation
        $reservationElement->findElement(WebDriverBy::xpath(".//*[@dusk='btnCancelReservation']"))->click();

        $browser->waitFor('@modalProductReserveCancel');
        $browser->within('@modalProductReserveCancel', function(Browser $browser) {
            $browser->click('@btnSubmit');
        });

        $browser->waitUntilMissingText($reservation->code);

        $reservationElement = $this->findReservationElement($browser, $productName, $reservation->code);
        $this->assertNull($reservationElement);

        $reservation->refresh();
        $this->assertNotNull($reservation->deleted_at);
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
    private function findFundElement(Browser $browser, string $fundTitle): ?RemoteWebElement
    {
        $selector = '@fundItem';

        $browser->waitFor($selector);
        foreach ($browser->elements($selector) as $element) {
            $text = $element->findElement(WebDriverBy::xpath(".//*[@dusk='fundName']"))->getText();

            if (trim($text) === $fundTitle) {
                return $element;
            }
        }

        return null;
    }

    /**
     * @param Browser $browser
     * @param string $productName
     * @param string $code
     * @return RemoteWebElement|null
     */
    private function findReservationElement(
        Browser $browser,
        string $productName,
        string $code
    ): ?RemoteWebElement {
        $selector = '@reservationItem';

        try {
            $browser->waitFor($selector);
        } catch (TimeOutException $e) {
            return null;
        }

        try{
            foreach ($browser->elements($selector) as $element) {
                $productNameElement = $element->findElement(WebDriverBy::xpath(".//*[@dusk='productName']"));
                $reservationLabelElement = $element->findElement(WebDriverBy::xpath(".//*[@dusk='labelPending']"));
                $reservationCodeElement = $element->findElement(WebDriverBy::xpath(".//*[@dusk='reservationCode']"));

                if (
                    $reservationLabelElement &&
                    trim($productNameElement->getText()) === $productName &&
                    Str::contains($reservationCodeElement->getText(), $code)
                ) {
                    return $element;
                }
            }
        } catch (NoSuchElementException $e) {}

        return null;
    }

    /**
     * @param Browser $browser
     * @return void
     * @throws \Facebook\WebDriver\Exception\TimeOutException
     */
    private function goToVouchersPage(Browser $browser): void
    {
        $browser->waitFor('@identityEmail');
        $browser->assertSeeIn('@identityEmail', $this->identity->email);

        $browser->waitFor('@userVouchers');
        $browser->element('@userVouchers')->click();
    }

    /**
     * @param Browser $browser
     * @return void
     * @throws TimeOutException
     */
    private function logout(Browser $browser): void
    {
        $browser->element('@userProfile')->click();
        $browser->waitFor('@btnUserLogout');
        $browser->element('@btnUserLogout')->click();
    }
}
