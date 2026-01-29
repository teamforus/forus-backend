<?php

namespace Tests\Browser\Traits;

use App\Models\Identity;
use App\Models\Organization;
use Facebook\WebDriver\Exception\ElementClickInterceptedException;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\TimeoutException;
use Facebook\WebDriver\Remote\LocalFileDetector;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\WebDriverBy;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use Laravel\Dusk\Browser;
use RuntimeException;
use Tests\Traits\MakesTestIdentities;
use Throwable;

trait HasFrontendActions
{
    use MakesTestIdentities;

    /**
     * @param Browser $browser
     * @param string $selector
     * @param int $count
     * @param string $operator
     * @param string|null $message
     * @throws TimeoutException
     * @return Browser
     */
    public function waitForNumber(
        Browser $browser,
        string $selector,
        int $count,
        string $operator,
        string $message = null,
    ): Browser {
        return $browser->waitUsing(null, 100, function () use ($browser, $selector, $count, $operator) {
            $scriptSelector = str_replace('@', '', $selector);
            $value = (int) $browser->script("return document.querySelector('[data-dusk=\"$scriptSelector\"]')?.textContent;")[0];

            return match ($operator) {
                '=' => $value === $count,
                '>=' => $value >= $count,
                '<=' => $value <= $count,
                '>' => $value > $count,
                '<' => $value < $count,
            };
        }, $message);
    }

    /**
     * @param Browser $browser
     * @throws TimeoutException
     * @return void
     */
    protected function goSponsorProfilesPage(Browser $browser): void
    {
        $browser->waitFor('@asideMenuGroupIdentities');
        $browser->element('@asideMenuGroupIdentities')->click();
        $browser->waitFor('@identitiesPage');
        $browser->element('@identitiesPage')->click();
    }

    /**
     * @param Browser $browser
     * @throws TimeoutException
     * @return void
     */
    protected function goSponsorPayoutsPage(Browser $browser): void
    {
        $browser->waitFor('@asideMenuGroupVouchers');
        $browser->element('@asideMenuGroupVouchers')->click();
        $browser->waitFor('@payoutsNav');
        $browser->element('@payoutsNav')->click();
    }

    /**
     * @param Browser $browser
     * @param int $count
     * @param string $selector
     * @param string $operator
     * @return void
     */
    protected function assertWebshopRowsCount(
        Browser $browser,
        int $count,
        string $selector,
        string $operator = '=',
    ): void {
        $this->assertRowsCount($browser, $count, $selector, $operator, $this->getWebshopRowsSelector());
    }

    /**
     * @return string
     */
    protected function getWebshopRowsSelector(): string
    {
        return '[data-search-item]';
    }

    /**
     * @param Browser $browser
     * @param string $selector
     * @throws TimeoutException
     * @return void
     */
    protected function clearField(Browser $browser, string $selector): void
    {
        $browser->waitFor($selector);

        try {
            $browser->click($selector)
                ->keys($selector, [PHP_OS_FAMILY === 'Darwin' ? '{command}' : '{control}', 'a'])
                ->keys($selector, '{delete}')
                ->pause(100);

            $value = $browser->value($selector);

            if (!is_string($value)) {
                throw new RuntimeException("Expected string from value([$selector]), got " . gettype($value));
            }

            if (!empty($value)) {
                throw new RuntimeException("Failed to clear field [$selector]; value is still: '$value'");
            }
        } catch (Throwable $e) {
            throw new RuntimeException("Unable to clear field [$selector]: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * @param Browser $browser
     * @param int $count
     * @param bool $waitForItems
     * @param string|null $filePath
     * @return void
     * @throws TimeoutException
     */
    protected function attachFilesToFileUploader(
        Browser $browser,
        int $count = 1,
        bool $waitForItems = true,
        ?string $filePath = null,
    ): void {
        $filePath ??= base_path('tests/assets/test.png');
        $browser->script("document.querySelectorAll('.droparea-hidden-input').forEach((el) => el.style.display = 'block')");
        $browser->waitFor("input[name='file_uploader_input_hidden']");

        $inputs = $browser->elements("input[name='file_uploader_input_hidden']");
        $this->assertGreaterThanOrEqual($count, count($inputs));

        for ($i = 0; $i < $count; $i++) {
            $inputs[$i]->setFileDetector(new LocalFileDetector())->sendKeys($filePath);
        }

        if ($waitForItems) {
            $browser->waitFor('.file-item');
            $browser->waitUntilMissing('.file-item-uploading');
        }

        $browser->script("document.querySelectorAll('.droparea-hidden-input').forEach((el) => el.style.display = 'none')");
    }

    /**
     * @param Browser $browser
     * @param Identity $identity
     * @throws TimeOutException
     * @return void
     */
    protected function assertIdentityAuthenticatedOnValidatorDashboard(Browser $browser, Identity $identity): void
    {
        $this->assertIdentityAuthenticatedFrontend($browser, $identity, 'validator');
    }

    /**
     * @param Browser $browser
     * @param Identity $identity
     * @return void
     */
    protected function loginIdentity(Browser $browser, Identity $identity): void
    {
        $browser->script('localStorage.clear();');
        $browser->refresh();
        $proxy = $this->makeIdentityProxy($identity);
        $browser->script("localStorage.active_account = '$proxy->access_token';");
        $browser->refresh();
    }

    /**
     * @param Browser $browser
     * @param Identity $identity
     * @throws TimeoutException
     * @return void
     */
    protected function assertIdentityAuthenticatedOnWebshop(Browser $browser, Identity $identity): void
    {
        $this->assertIdentityAuthenticatedFrontend($browser, $identity, 'webshop');
    }

    /**
     * @param Browser $browser
     * @param Identity $identity
     * @throws TimeoutException
     * @return void
     */
    protected function assertIdentityAuthenticatedOnSponsorDashboard(
        Browser $browser,
        Identity $identity
    ): void {
        $this->assertIdentityAuthenticatedFrontend($browser, $identity, 'sponsor');
    }

    /**
     * @param Browser $browser
     * @param Identity $identity
     * @throws TimeOutException
     * @return void
     */
    protected function assertIdentityAuthenticatedOnProviderDashboard(Browser $browser, Identity $identity): void
    {
        $this->assertIdentityAuthenticatedFrontend($browser, $identity, 'provider');
    }

    /**
     * @param Browser $browser
     * @param Identity $identity
     * @param string $frontend
     * @throws TimeOutException
     * @return void
     */
    protected function assertIdentityAuthenticatedFrontend(
        Browser $browser,
        Identity $identity,
        string $frontend,
    ): void {
        $browser->waitFor(match ($frontend) {
            'webshop' => $identity->email ? '@identityEmail' : '@userVouchers',
            'sponsor' => '@fundsTitle',
            'provider' => '@providerOverview',
            'validator' => '@tableFundRequestContent',
        }, 10);

        if ($identity->email) {
            $browser->assertSeeIn('@identityEmail', $identity->email);
        }
    }

    /**
     * @param Browser $browser
     * @param string $selector
     * @param string|null $text
     * @param int|null $index
     * @param bool $assertExists
     * @return RemoteWebElement|null
     */
    protected function findOptionElement(
        Browser $browser,
        string $selector,
        string $text = null,
        int $index = null,
        bool $assertExists = true
    ): ?RemoteWebElement {
        $option = null;

        $browser->elsewhereWhenAvailable($selector . 'Options', function (Browser $browser) use (&$option, &$index, &$text) {
            $xpath = WebDriverBy::xpath(".//*[contains(@class, 'select-control-option') and not(contains(@class, 'select-control-options'))]");
            $options = $browser->driver->findElements($xpath);

            if ($text !== null) {
                $option = Arr::first($options, fn (RemoteWebElement $element) => trim($element->getText()) === $text);
            }

            if ($index !== null) {
                $option = $options[$index] ?? null;
            }
        });

        $assertExists
            ? $this->assertNotNull($option, "No option found in $selector.")
            : $this->assertNull($option, "Option found in $selector.");

        return $option;
    }

    /**
     * @param Browser $browser
     * @param string $selector
     * @param string|null $text
     * @param int|null $index
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeoutException
     * @return void
     */
    protected function changeSelectControl(Browser $browser, string $selector, string $text = null, int $index = null): void
    {
        $browser->waitFor($selector);
        $browser->click("$selector .select-control-search");

        $this->findOptionElement($browser, $selector, text: $text, index: $index)->click();
    }

    /**
     * @param Browser $browser
     * @param int $count
     * @param string $selector
     * @param string $operator
     * @param string $rowSelector
     * @return void
     */
    protected function assertRowsCount(
        Browser $browser,
        int $count,
        string $selector,
        string $operator = '=',
        string $rowSelector = 'tbody>tr',
    ): void {
        $browser->within($selector, function (Browser $browser) use ($count, $operator, $selector, $rowSelector) {
            if ($count === 0 && $operator === '=') {
                $browser->waitUntilMissing('@paginatorTotal');
                $browser->waitUntilMissing($rowSelector);
            } else {
                $this->waitForNumber(
                    $browser,
                    '@paginatorTotal',
                    $count,
                    $operator,
                    "Timed out waiting for paginator total to be $operator $count (selector \"$selector\").",
                );
            }

            $rows = $browser->elements($rowSelector);
            $rowCount = count($rows);

            $message = "Assertion failed for \"$selector\": expected rows $operator $count, got $rowCount.";

            match ($operator) {
                '=' => $this->assertCount($count, $rows, $message),
                '>=' => $this->assertGreaterThanOrEqual($count, $rowCount, $message),
                '<=' => $this->assertLessThanOrEqual($count, $rowCount, $message),
                '>' => $this->assertGreaterThan($count, $rowCount, $message),
                '<' => $this->assertLessThan($count, $rowCount, $message),
                default => throw new InvalidArgumentException("Invalid operator \"$operator\""),
            };
        });
    }

    /**
     * @param Browser $browser
     * @throws TimeoutException
     * @throws \Facebook\WebDriver\Exception\ElementClickInterceptedException
     * @throws \Facebook\WebDriver\Exception\NoSuchElementException
     * @return void
     */
    protected function assertAndCloseSuccessNotification(Browser $browser): void
    {
        $browser->waitFor('@successNotification');
        $browser->click('@successNotification @notificationCloseBtn');
        $browser->waitUntilMissing('@successNotification');
    }

    /**
     * @param Browser $browser
     * @throws TimeOutException
     * @return void
     */
    protected function logout(Browser $browser): void
    {
        $browser->pause(100);

        // close all filters if not closed before logout - filters can be over logout btn
        if ($browser->element('@hideFilters')) {
            $browser->element('@hideFilters')->click();
            $browser->waitFor('@showFilters');
        }

        $browser->waitFor('@userProfile');
        $browser->scrollIntoView('@userProfile');
        $browser->element('@userProfile')->click();

        $browser->waitFor('@btnUserLogout')->waitFor('@btnUserLogout');
        $browser->element('@btnUserLogout')->click();

        $browser->waitUntilMissing('@userProfile');
    }

    /**
     * @param Browser $browser
     * @param Organization $organization
     * @throws TimeOutException
     * @return void
     */
    protected function selectDashboardOrganization(
        Browser $browser,
        Organization $organization,
    ): void {
        $browser->waitFor('@headerOrganizationSwitcher');
        $browser->press('@headerOrganizationSwitcher');
        $browser->waitFor("@headerOrganizationItem$organization->id");
        $browser->press("@headerOrganizationItem$organization->id");
    }

    /**
     * @param Browser $browser
     * @param int $fundId
     * @throws TimeOutException
     * @return void
     */
    protected function switchToFund(Browser $browser, int $fundId): void
    {
        $browser->waitFor('@selectControlFunds');
        $browser->element('@selectControlFunds')->click();

        $browser->waitFor("@selectControlFundItem$fundId");
        $browser->element("@selectControlFundItem$fundId")->click();
    }

    /**
     * @param Browser $browser
     * @param string $selector
     * @param string $value
     * @param string|null $id
     * @param int $expected
     * @throws TimeoutException
     * @return void
     */
    protected function searchTable(
        Browser $browser,
        string $selector,
        string $value,
        ?string $id,
        int $expected = 1,
    ): void {
        $browser->waitFor($selector . 'Search');
        $browser->typeSlowly($selector . 'Search', $value, 50);

        if ($id !== null) {
            $browser->waitFor($selector . "Row$id");
            $browser->assertVisible($selector . "Row$id");
        }

        $this->assertRowsCount($browser, $expected, $selector . 'Content');
    }

    /**
     * @param Browser $browser
     * @param string $selector
     * @param string $value
     * @param string|null $id
     * @param int $expected
     * @throws TimeoutException
     * @return void
     */
    protected function searchWebshopList(
        Browser $browser,
        string $selector,
        string $value,
        ?string $id,
        int $expected = 1,
    ): void {
        $browser->waitFor($selector . 'Search');
        $browser->typeSlowly($selector . 'Search', $value, 50);

        if ($id !== null) {
            $browser->waitFor($selector . "Row$id");
            $browser->assertVisible($selector . "Row$id");
        }

        $this->assertWebshopRowsCount($browser, $expected, $selector . 'Content');
    }

    /**
     * @param Browser $browser
     * @param string $selector
     * @param string|int|null $value
     * @throws TimeoutException
     * @return void
     */
    protected function clearInputCustom(
        Browser $browser,
        string $selector,
        string|int|null $value = null
    ): void {
        if ($selector === '@controlDate') {
            return;
        }

        if ($selector === '@controlStep') {
            $browser->waitFor($selector);
            $browser->within($selector, function (Browser $browser) use ($value) {
                for ($i = 0; $i < $value; $i++) {
                    $browser->click('@decreaseStep');
                }
            });

            return;
        }

        /** @var string $value */
        $value = $browser->value($selector);
        $browser->keys($selector, ...array_fill(0, strlen($value), '{backspace}'));
    }

    /**
     * @param Browser $browser
     * @param string $selector
     * @param string $control
     * @param string|int|null $value
     * @throws TimeoutException
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @return void
     */
    protected function fillInput(Browser $browser, string $selector, string $control, string|int|null $value): void
    {
        switch ($control) {
            case 'select':
                $browser->waitFor($selector);
                $this->changeSelectControl($browser, $selector, $value);
                break;
            case 'number':
            case 'currency':
            case 'text':
                $browser->waitFor($selector);
                $browser->type($selector, $value);
                break;
            case 'checkbox':
                $value && $browser->waitFor($selector)->click($selector);
                break;
            case 'step':
                $browser->waitFor($selector);
                $browser->within($selector, function (Browser $browser) use ($value) {
                    for ($i = 0; $i < $value; $i++) {
                        $browser->click('@increaseStep');
                    }
                });
                break;
            case 'date':
                $browser->waitFor($selector);
                $this->clearInputCustom($browser, "$selector input[type='text']");
                $browser->type("$selector input[type='text']", $value);
                break;
        }
    }

    /**
     * @param Browser $browser
     * @param string $selector
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @return void
     */
    protected function uncollapseWebshopFilterGroup(Browser $browser, string $selector): void
    {
        $element = $browser->element($selector);

        if ($element && !str_contains($element?->getAttribute('class') ?: '', 'showcase-aside-group-open')) {
            $browser->click($selector . ' .showcase-aside-group-title-toggle');
        }
    }
}
