<?php

namespace Tests\Browser;

use App\Models\Identity;
use App\Models\Implementation;
use App\Searches\FundRequestSearch;
use App\Services\MailDatabaseLoggerService\Traits\AssertsSentEmails;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\HasFrontendActions;
use Tests\Browser\Traits\NavigatesFrontendWebshop;
use Tests\DuskTestCase;
use Throwable;

class FundRequestTest extends DuskTestCase
{
    use AssertsSentEmails;
    use HasFrontendActions;
    use NavigatesFrontendWebshop;
    use WithFaker;

    protected ?Identity $identity;

    /**
     * @throws Throwable
     * @return void
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
                'identity_id' => $identity->id,
            ])->take(10)->get();

            $this->goToIdentityFundRequests($browser);

            foreach ($fundRequests as $request) {
                $element = '@listFundRequestsRow' . $request->id;
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
}
