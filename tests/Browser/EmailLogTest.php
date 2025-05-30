<?php

namespace Tests\Browser;

use App\Mail\Funds\FundRequests\FundRequestCreatedMail;
use App\Mail\Vouchers\VoucherAssignedBudgetMail;
use App\Models\Fund;
use App\Models\FundRequest;
use App\Models\Identity;
use App\Models\Implementation;
use App\Services\MailDatabaseLoggerService\Models\EmailLog;
use App\Services\MailDatabaseLoggerService\Traits\AssertsSentEmails;
use Carbon\Carbon;
use Facebook\WebDriver\Exception\ElementClickInterceptedException;
use Facebook\WebDriver\Exception\NoSuchElementException;
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

                $this->goSponsorProfilesPage($browser);
                $this->searchTable($browser, '@tableProfiles', $identity->email, $identity->id);
                $browser->click("@tableProfilesRow$identity->id");

                $log = $this->findEmailLog($identity, VoucherAssignedBudgetMail::class, $this->startTime);

                $this->assertEmailLogsExistAreVisibleAndExportable($browser, $log);
                $this->assertDontSeeUnrelatedEmailLogsFromTheSameOrganization($browser, $log, $fund);

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

                $this->goToFundRequestsPage($browser);
                $this->searchTable($browser, '@tableFundRequest', $fundRequest->identity->email, $fundRequest->id);
                $browser->click("@tableFundRequestRow$fundRequest->id");

                $log = $this->findEmailLog($identity, FundRequestCreatedMail::class, $this->startTime);

                $this->assertEmailLogsExistAreVisibleAndExportable($browser, $log);
                $this->assertDontSeeUnrelatedEmailLogsFromTheSameOrganization($browser, $log, $fund, $fundRequest);

                // Logout
                $this->logout($browser);
            });
        }, function () use ($fund) {
            $fund && $this->deleteFund($fund);
        });
    }

    /**
     * @param Browser $browser
     * @param EmailLog $log
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeoutException
     * @return void
     */
    protected function assertEmailLogsExistAreVisibleAndExportable(Browser $browser, EmailLog $log): void
    {
        $browser->waitFor('@emailLogs');
        $browser->waitFor("@emailLogRow$log->id");
        $browser->assertVisible("@emailLogRow$log->id");

        $this->assertViewModal($browser, $log);
        $this->assertExportEmail($browser, $log);
    }

    /**
     * @param Browser $browser
     * @param EmailLog $log
     * @param FundRequest|null $fundRequest
     * @param Fund $fund
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeOutException
     * @return void
     */
    protected function assertDontSeeUnrelatedEmailLogsFromTheSameOrganization(
        Browser $browser,
        EmailLog $log,
        Fund $fund,
        FundRequest $fundRequest = null,
    ): void {
        $identity = $this->makeIdentity($this->makeUniqueEmail());

        // assert access for other cases
        if ($fundRequest) {
            // assert that employee doesn't see log for other fund_request (related to organization)
            $otherFundRequest = $this->setCriteriaAndMakeFundRequest($identity, $fund, [
                'children_nth' => 3,
            ]);

            $this->goToFundRequestsPage($browser);
            $this->searchTable($browser, '@tableFundRequest', $otherFundRequest->identity->email, $otherFundRequest->id);
            $browser->click("@tableFundRequestRow$otherFundRequest->id");
        } else {
            // assert that employee doesn't see log for other identity (related to organization)
            $fund->makeVoucher($identity);

            $this->goSponsorProfilesPage($browser);
            $this->searchTable($browser, '@tableProfiles', $identity->email, $identity->id);
            $browser->click("@tableProfilesRow$identity->id");
        }

        $browser->waitFor('@emailLogs');
        $browser->assertMissing("@emailLogRow$log->id");
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
