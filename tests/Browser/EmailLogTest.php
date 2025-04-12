<?php

namespace Browser;

use App\Mail\Funds\FundRequests\FundRequestCreatedMail;
use App\Mail\Vouchers\VoucherAssignedBudgetMail;
use App\Models\Fund;
use App\Models\FundRequest;
use App\Models\Identity;
use App\Models\Implementation;
use App\Services\MailDatabaseLoggerService\Models\EmailLog;
use App\Services\MailDatabaseLoggerService\Traits\AssertsSentEmails;
use Carbon\Carbon;
use Facebook\WebDriver\Exception\TimeOutException;
use Illuminate\Support\Facades\Cache;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\HasFrontendActions;
use Tests\Browser\Traits\RollbackModelsTrait;
use Tests\DuskTestCase;
use Tests\Traits\MakesTestFundRequests;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestIdentities;
use Throwable;

class EmailLogTest extends DuskTestCase
{
    use MakesTestFunds;
    use AssertsSentEmails;
    use HasFrontendActions;
    use MakesTestIdentities;
    use RollbackModelsTrait;
    use MakesTestFundRequests;

    protected ?Carbon $startTime = null;

    /**
     * @throws Throwable
     * @return void
     */
    public function testEmailLogForIdentity(): void
    {
        $this->startTime = now();
        Cache::clear();

        $implementation = Implementation::byKey('nijmegen');
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $fund = $this->makeTestFund($implementation->organization);
        $fund->makeVoucher($identity);

        $this->rollbackModels([], function () use ($implementation, $fund, $identity) {
            $this->browse(function (Browser $browser) use ($implementation, $fund, $identity) {
                $organization = $implementation->organization;
                $browser->visit($implementation->urlSponsorDashboard());

                // Authorize identity
                $this->loginIdentity($browser, $organization->identity);
                $this->assertIdentityAuthenticatedOnSponsorDashboard($browser, $organization->identity);
                $this->selectDashboardOrganization($browser, $organization);

                $this->goToIdentitiesListPage($browser);
                $this->searchIdentity($browser, $identity);

                $this->assertLogExists($browser, $identity, $fund, VoucherAssignedBudgetMail::class);

                // Logout
                $this->logout($browser);
            });
        }, function () use ($fund) {
            $fund && $this->deleteFund($fund);
        });
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testEmailLogForFundRequest(): void
    {
        $this->startTime = now();
        Cache::clear();

        $implementation = Implementation::byKey('nijmegen');
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $fund = $this->makeTestFund($implementation->organization, [], [
            'allow_fund_requests' => true,
        ]);

        $fundRequest = $this->setCriteriaAndMakeFundRequest($identity, $fund, [
            'children_nth' => 3,
        ]);

        $this->rollbackModels([], function () use ($implementation, $fund, $identity, $fundRequest) {
            $this->browse(function (Browser $browser) use ($implementation, $fund, $identity, $fundRequest) {
                $organization = $implementation->organization;
                $browser->visit($implementation->urlSponsorDashboard());

                // Authorize identity
                $this->loginIdentity($browser, $organization->identity);
                $this->assertIdentityAuthenticatedOnSponsorDashboard($browser, $organization->identity);
                $this->selectDashboardOrganization($browser, $organization);

                $this->goToFundRequestsListPage($browser);
                $this->searchFundRequest($browser, $fundRequest);

                $this->assertLogExists($browser, $identity, $fund, FundRequestCreatedMail::class, $fundRequest);

                // Logout
                $this->logout($browser);
            });
        }, function () use ($fund) {
            $fund && $this->deleteFund($fund);
        });
    }

    /**
     * @param Browser $browser
     * @throws TimeoutException
     * @return void
     */
    protected function goToIdentitiesListPage(Browser $browser): void
    {
        $browser->waitFor('@asideMenuGroupIdentities');
        $browser->element('@asideMenuGroupIdentities')->click();
        $browser->waitFor('@identitiesPage');
        $browser->element('@identitiesPage')->click();
    }

    /**
     * @param Browser $browser
     * @param Identity $identity
     * @throws TimeoutException
     * @throws \Facebook\WebDriver\Exception\ElementClickInterceptedException
     * @throws \Facebook\WebDriver\Exception\NoSuchElementException
     * @return void
     */
    protected function searchIdentity(Browser $browser, Identity $identity): void
    {
        $browser->waitFor('@searchIdentities');
        $browser->type('@searchIdentities', $identity->email);

        $browser->waitFor("@identityRow$identity->id", 20);
        $browser->assertVisible("@identityRow$identity->id");

        $browser->click("@identityRow$identity->id");
    }

    /**
     * @param Browser $browser
     * @throws TimeoutException
     * @return void
     */
    protected function goToFundRequestsListPage(Browser $browser): void
    {
        $browser->waitFor('@asideMenuGroupFundRequests');
        $browser->element('@asideMenuGroupFundRequests')->click();
        $browser->waitFor('@fundRequestsPage');
        $browser->element('@fundRequestsPage')->click();
    }

    /**
     * @param Browser $browser
     * @param FundRequest $fundRequest
     * @throws TimeoutException
     * @throws \Facebook\WebDriver\Exception\ElementClickInterceptedException
     * @throws \Facebook\WebDriver\Exception\NoSuchElementException
     * @return void
     */
    protected function searchFundRequest(Browser $browser, FundRequest $fundRequest): void
    {
        $browser->waitFor('@searchFundRequests');
        $browser->type('@searchFundRequests', $fundRequest->identity->email);

        $browser->waitFor("@fundRequestRow$fundRequest->id", 20);
        $browser->assertVisible("@fundRequestRow$fundRequest->id");

        $browser->click("@fundRequestRow$fundRequest->id");
    }

    /**
     * @param Browser $browser
     * @param Identity $identity
     * @param Fund $fund
     * @param string $mailable
     * @param FundRequest|null $fundRequest
     * @throws TimeOutException
     * @throws \Facebook\WebDriver\Exception\ElementClickInterceptedException
     * @throws \Facebook\WebDriver\Exception\NoSuchElementException
     * @return void
     */
    protected function assertLogExists(
        Browser $browser,
        Identity $identity,
        Fund $fund,
        string $mailable,
        ?FundRequest $fundRequest = null
    ): void {
        $this->assertMailableSent($identity->email, $mailable, $this->startTime);

        /** @var EmailLog $log */
        $log = $this->getEmailOfTypeQuery($identity->email, $mailable, $this->startTime)->first();
        $this->assertNotNull($log);

        $browser->waitFor('@emailLogs');
        $browser->waitFor("@emailLogRow$log->id");
        $browser->assertVisible("@emailLogRow$log->id");

        $this->assertViewModal($browser, $log);
        $this->assertExportEmail($browser, $log);

        // assert access for other cases
        if ($fundRequest) {
            // assert that employee doesn't see log for other fund_request (related to organization)
            $otherIdentity = $this->makeIdentity($this->makeUniqueEmail());
            $otherFundRequest = $this->setCriteriaAndMakeFundRequest($otherIdentity, $fund, [
                'children_nth' => 3,
            ]);

            $this->goToFundRequestsListPage($browser);
            $this->searchFundRequest($browser, $otherFundRequest);

            $browser->waitFor('@emailLogs');
            $browser->assertMissing("@emailLogRow$log->id");
        } else {
            // assert that employee doesn't see log for other identity (related to organization)
            $otherIdentity = $this->makeIdentity($this->makeUniqueEmail());
            $fund->makeVoucher($otherIdentity);

            $this->goToIdentitiesListPage($browser);
            $this->searchIdentity($browser, $otherIdentity);

            $browser->waitFor('@emailLogs');
            $browser->assertMissing("@emailLogRow$log->id");
        }
    }

    /**
     * @param Browser $browser
     * @param EmailLog $log
     * @throws TimeoutException
     * @throws \Facebook\WebDriver\Exception\ElementClickInterceptedException
     * @throws \Facebook\WebDriver\Exception\NoSuchElementException
     * @return void
     */
    protected function assertViewModal(Browser $browser, EmailLog $log): void
    {
        $browser->waitFor("@btnEmailLogMenu$log->id");
        $browser->click("@btnEmailLogMenu$log->id");
        $browser->waitFor('@openEmail');
        $browser->click('@openEmail');

        $browser->waitFor('@modalLogEmailShow');
        $browser->within('@modalLogEmailShow', function (Browser $browser) use ($log) {
            $browser->waitForText($log->to_address);
            $browser->assertSee($log->to_address);
            $browser->assertSee($log->from_address);
            $browser->assertSee($log->subject);

            // clear content
            $content = preg_replace('#<style[^>]*>.*?</style>#is', '', $log->content);
            $content = trim(strip_tags($content));
            // get first sentence
            $content = explode('.', $content)[0];

            $browser->assertSee($content);
        });

        $browser->click('@closeModalButton');
    }

    /**
     * @param Browser $browser
     * @param EmailLog $log
     * @throws TimeoutException
     * @throws \Facebook\WebDriver\Exception\ElementClickInterceptedException
     * @throws \Facebook\WebDriver\Exception\NoSuchElementException
     * @return void
     */
    protected function assertExportEmail(Browser $browser, EmailLog $log): void
    {
        $browser->waitFor("@btnEmailLogMenu$log->id");
        $browser->click("@btnEmailLogMenu$log->id");
        $browser->waitFor('@exportEmail');
        $browser->click('@exportEmail');

        $browser->pause(1000);
        $browser->assertMissing('@dangerNotification');
    }
}
