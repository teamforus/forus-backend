<?php

namespace Tests\Browser\Traits;

use App\Models\Fund;
use App\Models\Identity;
use App\Models\Implementation;
use App\Models\ImplementationPage;
use Facebook\WebDriver\Exception\ElementClickInterceptedException;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\TimeoutException;
use Laravel\Dusk\Browser;

trait HasFundRequestFormActions
{
    /**
     * @param Browser $browser
     * @param Implementation $implementation
     * @param Fund $fund
     * @param Identity $requester
     * @param bool $assertForm
     * @throws NoSuchElementException
     * @throws TimeoutException
     * @throws ElementClickInterceptedException
     * @return void
     */
    protected function openFundRequestFormByDigiD(
        Browser $browser,
        Implementation $implementation,
        Fund $fund,
        Identity $requester,
        bool $assertForm = true,
    ): void {
        $browser->visit($implementation->urlWebshop());
        $this->loginIdentity($browser, $requester);
        $browser->waitFor('@headerTitle');

        $browser->visit($implementation->urlWebshop("fondsen/$fund->id"));
        $browser->waitFor('@fundTitle');
        $browser->assertSeeIn('@fundTitle', $fund->name);

        $browser->waitFor('@requestButton')->click('@requestButton');
        $browser->waitFor('@digidOption')->click('@digidOption');

        if ($assertForm) {
            $browser->waitFor('@fundRequestForm');
        }
    }

    /**
     * @param Browser $browser
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeoutException
     * @return void
     */
    protected function skipEmailStep(Browser $browser): void
    {
        $browser->waitFor('@fundRequestEmailForm');
        $browser->click('@fundRequestSkipEmail');
        $browser->waitFor('@fundRequestContinueWithoutEmail');
        $browser->click('@fundRequestContinueWithoutEmail');
    }

    /**
     * @param Browser $browser
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeoutException
     * @return void
     */
    protected function acceptPrivacyAndTerms(Browser $browser): void
    {
        $browser->waitFor('#privacy');
        $browser->click('#privacy');
        $browser->waitFor('#terms');
        $browser->click('#terms');
    }

    /**
     * @param Browser $browser
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeoutException
     * @return void
     */
    protected function acceptConfirmCriteriaIfPresent(Browser $browser): void
    {
        if (!$browser->element('@confirmCriteriaCheckbox')) {
            return;
        }

        $browser->waitFor('@confirmCriteriaCheckbox');
        $browser->click('@confirmCriteriaCheckbox');

        if ($browser->element('@confirmCriteriaWarningCheckbox')) {
            $browser->waitFor('@confirmCriteriaWarningCheckbox');
            $browser->click('@confirmCriteriaWarningCheckbox');
        }

        $browser->waitFor('@nextStepButton')->click('@nextStepButton');
    }

    /**
     * @param Browser $browser
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeoutException
     * @return void
     */
    protected function clickFooterAction(Browser $browser): void
    {
        $browser->waitFor('.sign_up-pane-footer');
        $browser->click('.sign_up-pane-footer .flex.flex-horizontal .flex:last-child button');
    }

    /**
     * @param Implementation $implementation
     * @return array
     */
    protected function ensurePrivacyAndTermsImplementationPages(Implementation $implementation): array
    {
        $privacyPage = $this->firstOrCreateImplementationPage(
            $implementation,
            ImplementationPage::TYPE_PRIVACY,
            'Privacy',
        );

        $termsPage = $this->firstOrCreateImplementationPage(
            $implementation,
            ImplementationPage::TYPE_TERMS_AND_CONDITIONS,
            'Terms',
        );

        return [$privacyPage, $termsPage];
    }

    /**
     * @param Implementation $implementation
     * @param string $pageType
     * @param string $title
     * @return ImplementationPage
     */
    protected function firstOrCreateImplementationPage(
        Implementation $implementation,
        string $pageType,
        string $title,
    ): ImplementationPage {
        return ImplementationPage::firstOrCreate([
            'implementation_id' => $implementation->id,
            'page_type' => $pageType,
        ], [
            'state' => ImplementationPage::STATE_PUBLIC,
            'title' => $title,
            'description' => $title . ' content',
        ]);
    }
}
