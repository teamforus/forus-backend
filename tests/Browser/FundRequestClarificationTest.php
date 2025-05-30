<?php

namespace Tests\Browser;

use App\Mail\Auth\UserLoginMail;
use App\Models\FundRequest;
use App\Services\MailDatabaseLoggerService\Traits\AssertsSentEmails;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\HasFrontendActions;
use Tests\DuskTestCase;
use Tests\Traits\MakesTestFundRequests;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestIdentities;
use Tests\Traits\MakesTestOrganizations;
use Throwable;

class FundRequestClarificationTest extends DuskTestCase
{
    use AssertsSentEmails;
    use HasFrontendActions;
    use WithFaker;
    use MakesTestFunds;
    use MakesTestOrganizations;
    use MakesTestIdentities;
    use MakesTestFundRequests;

    /**
     * @throws Throwable
     * @return void
     */
    public function testWebshopFundRequestClarificationRedirect(): void
    {
        $startTime = now();
        $headers = [
            'Accept' => 'application/json',
            'client_type' => 'webshop',
        ];

        // create sponsor and requester identities
        $sponsorIdentity = $this->makeIdentity($this->makeUniqueEmail());
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $identity->setBsnRecord('123456789');

        // create the organization and fund
        $organization = $this->makeTestOrganization($sponsorIdentity);
        $fund = $this->makeTestFund($organization);

        $records = [[
            'fund_criterion_id' => $fund->criteria[0]?->id,
            'value' => 5,
            'files' => [],
        ]];

        // create fund request and assert email log created
        $response = $this->makeFundRequest($identity, $fund, $records, false, $headers);
        $response->assertSuccessful();
        /** @var FundRequest $fundRequest */
        $fundRequest = FundRequest::find($response->json('data.id'));
        $this->assertNotNull($fundRequest);

        $this->assertFundRequestCreateEmailLog($organization, $fundRequest, $headers);

        $fundRequest->assignEmployee($organization->findEmployee($sponsorIdentity));

        $questionToken = $this->requestFundRequestClarification($organization, $fundRequest);

        $this->assertFundRequestClarificationEmailLog($organization, $fundRequest, $questionToken);

        $this->browse(function (Browser $browser) use ($fundRequest, $identity, $startTime) {
            $browser->visit($this->findFirstEmailFundRequestClarificationLink(
                $identity->email,
                $startTime
            ));

            $browser->waitFor('@authOptionEmailRestore');

            // Select the login by option
            $browser->element('@authOptionEmailRestore')->click();
            $browser->waitFor('@authEmailForm');
            $browser->assertVisible('@authEmailForm');

            // Type the email and submit the form
            $browser->within('@authEmailForm', function (Browser $browser) use ($identity) {
                $browser->click('@privacyCheckbox');
                $browser->type('@authEmailFormEmail', $identity->email);
                $browser->press('@authEmailFormSubmit');
            });

            // Await for email sent confirmation screen
            $browser->waitFor('@authEmailSentConfirmation');
            $browser->assertVisible('@authEmailSentConfirmation');

            // Check that the confirmation link was sent to the user by email
            $this->assertMailableSent($identity->email, UserLoginMail::class, $startTime);
            $this->assertEmailRestoreLinkSent($identity->email, $startTime);

            // Get and follow the auth link from the email then check if the user is authenticated
            $browser->visit($this->findFirstEmailRestoreLink($identity->email, $startTime));
            $this->assertIdentityAuthenticatedOnWebshop($browser, $identity);

            // assert requester was redirected to fund request page
            $browser->waitFor('@fundRequestFund');
            $browser->assertSeeIn('@fundRequestFund', $fundRequest->fund->name);

            // Logout identity
            $this->logout($browser);
        });
    }
}
