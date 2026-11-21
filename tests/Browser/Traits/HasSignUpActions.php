<?php

namespace Tests\Browser\Traits;

use App\Mail\User\EmailActivationMail;
use Facebook\WebDriver\Exception\ElementClickInterceptedException;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\TimeoutException;
use Illuminate\Support\Carbon;
use Laravel\Dusk\Browser;

/**
 * Shared steps of the dashboard sign-up flows (provider, sponsor).
 * Requires HasFrontendActions, AssertsSentEmails and MakesTestOrganizations on the test class.
 */
trait HasSignUpActions
{
    /**
     * @param Browser $browser
     * @param string $email
     * @param Carbon $startTime
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeoutException
     * @return void
     */
    protected function submitSignUpEmailForm(Browser $browser, string $email, Carbon $startTime): void
    {
        $browser
            ->waitFor('@emailForm')
            ->assertVisible('@emailForm');

        $this->fillSignUpInput($browser, '@emailInput', $email);

        $browser
            ->waitFor('@emailFormSubmit')
            ->click('@emailFormSubmit')
            ->waitFor('@authEmailSent')
            ->assertVisible('@authEmailSent');

        $this->assertMailableSent($email, EmailActivationMail::class, $startTime);
        $this->assertEmailConfirmationLinkSent($email, $startTime);

        $browser->visit($this->findFirstEmailConfirmationLink($email, $startTime));
    }

    /**
     * @param Browser $browser
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeoutException
     * @return string
     */
    protected function fillSignUpOrganizationForm(Browser $browser): string
    {
        $browser
            ->waitFor('@stepOrganizationAdd')
            ->assertVisible('@stepOrganizationAdd');

        $name = 'Test Organization ' . $this->faker->company();
        $iban = $this->makeIban();

        $this
            ->fillSignUpInput($browser, '@nameInput', $name, true)
            ->fillSignUpInput($browser, '@ibanInput', $iban, true)
            ->fillSignUpInput($browser, '@ibanConfirmationInput', $iban, true)
            ->fillSignUpInput($browser, '@emailInput', $this->makeUniqueEmail(), true)
            ->fillSignUpInput($browser, '@phoneInput', '31612345678', true)
            ->fillSignUpInput($browser, '@websiteInput', 'https://example.com', true);

        $browser
            ->waitFor('@emailPublicCheckbox')->click('@emailPublicCheckbox')
            ->waitFor('@phonePublicCheckbox')->click('@phonePublicCheckbox')
            ->waitFor('@websitePublicCheckbox')->click('@websitePublicCheckbox');

        $this->changeSelectControl($browser, '@businessTypeSelect', index: 0);

        $this
            ->fillSignUpInput($browser, '@kvkInput', '00000000', true)
            ->fillSignUpInput($browser, '@btwInput', 'NL123456789B01', true);

        return $name;
    }

    /**
     * @param Browser $browser
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeoutException
     * @return void
     */
    protected function assertSignUpOrganizationFormValidation(Browser $browser): void
    {
        $this->fillSignUpOrganizationForm($browser);

        $this->fillSignUpInput($browser, '@nameInput', '', true);
        $this->nextSignUpStep($browser);
        $browser->waitFor('@nameError1')->assertVisible('@nameError1');

        $this
            ->fillSignUpInput($browser, '@ibanInput', 'invalid_iban', true)
            ->fillSignUpInput($browser, '@ibanConfirmationInput', 'invalid_iban', true);
        $this->nextSignUpStep($browser);
        $browser->waitFor('@ibanError1')->assertVisible('@ibanError1');

        $this->fillSignUpInput($browser, '@emailInput', 'invalid@email', true);
        $this->nextSignUpStep($browser);
        $browser->waitFor('@emailError1')->assertVisible('@emailError1');

        $this->fillSignUpInput($browser, '@phoneInput', 'invalid_phone', true);
        $this->nextSignUpStep($browser);
        $browser->waitFor('@phoneError1')->assertVisible('@phoneError1');

        $this->fillSignUpInput($browser, '@websiteInput', 'invalid_website', true);
        $this->nextSignUpStep($browser);
        $browser->waitFor('@websiteError1')->assertVisible('@websiteError1');

        $this->fillSignUpInput($browser, '@kvkInput', 'invalid_kvk', true);
        $this->nextSignUpStep($browser);
        $browser->waitFor('@kvkError1')->assertVisible('@kvkError1');
        $browser->waitFor('@kvkError2')->assertVisible('@kvkError2');
    }

    /**
     * @param Browser $browser
     * @param string $selector
     * @param string|int $value
     * @param bool $clear
     * @throws TimeoutException
     * @return $this
     */
    protected function fillSignUpInput(
        Browser $browser,
        string $selector,
        string|int $value,
        bool $clear = false
    ): static {
        $browser->waitFor($selector);

        if ($clear) {
            $this->clearField($browser, $selector);
        }

        $browser->type($selector, $value);

        return $this;
    }

    /**
     * @param Browser $browser
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeoutException
     * @return void
     */
    protected function nextSignUpStep(Browser $browser): void
    {
        $browser->waitFor('@nextBtn')->click('@nextBtn');
    }

    /**
     * @param Browser $browser
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeoutException
     * @return void
     */
    protected function previousSignUpStep(Browser $browser): void
    {
        $browser->waitFor('@previousBtn')->click('@previousBtn');
    }

    /**
     * @param Browser $browser
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeoutException
     * @return void
     */
    protected function finishSignUp(Browser $browser): void
    {
        $browser
            ->waitFor('@stepFinished')
            ->assertVisible('@stepFinished')
            ->waitFor('@finishBtn')
            ->click('@finishBtn');
    }
}
