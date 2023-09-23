<?php

namespace Tests\Browser;

use App\Models\Identity;
use App\Models\Implementation;
use App\Searches\FundRequestSearch;
use App\Services\MailDatabaseLoggerService\Traits\AssertsSentEmails;
use Facebook\WebDriver\Exception\TimeOutException;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\HasFrontendActions;
use Tests\DuskTestCase;
use Illuminate\Foundation\Testing\WithFaker;

class FundRequestTest extends DuskTestCase
{
    use AssertsSentEmails, HasFrontendActions, WithFaker;

    protected ?Identity $identity;

    /**
     * @return void
     * @throws \Throwable
     */
    public function testWebshopFundRequests(): void
    {
        // Select implementation
        $implementation = Implementation::byKey('nijmegen');

        // Models exist
        $this->assertNotNull($implementation);
        $this->assertNotNull($implementation->organization);

        $this->browse(function (Browser $browser) use ($implementation) {
            $browser->visit($implementation->urlWebshop());

            $search = [
                'order_by' => 'no_answer_clarification',
                'archived' => false,
            ];

            $fundRequest = (new FundRequestSearch($search))->query()->first();
            $this->assertNotNull($fundRequest);

            $identity = $fundRequest->identity;

            $this->loginIdentity($browser, $identity);
            $browser->waitFor('@headerTitle');

            $fundRequests = (new FundRequestSearch($search))->query()->where([
                'identity_address' => $identity->address
            ])->take(10)->get();

            $this->goToIdentityFundRequests($browser);

            foreach ($fundRequests as $request) {
                $element = '@fundRequestsItem' . $request->id;
                $browser->waitFor($element);
                $browser->assertSeeIn($element, $request->fund->name);

                $browser->click($element);
                $browser->waitFor('@fundRequestFund');
                $browser->assertSeeIn('@fundRequestFund', $request->fund->name);

                $browser->back();
            }

            // Logout user
            $this->logout($browser);
        });
    }

    /**
     * @param Browser $browser
     * @return void
     * @throws TimeoutException
     */
    private function goToIdentityFundRequests(Browser $browser): void
    {
        $browser->waitFor('@userProfile');
        $browser->element('@userProfile')->click();
        $browser->waitFor('@btnFundRequests');
        $browser->element('@btnFundRequests')->click();
    }
}
