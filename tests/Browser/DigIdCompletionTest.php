<?php

namespace Tests\Browser;

use App\Models\Fund;
use App\Models\Identity;
use App\Models\Implementation;
use App\Services\DigIdService\Models\DigIdSession;
use App\Services\DigIdService\Objects\DigIdSessionData;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\HasFrontendActions;
use Tests\Browser\Traits\RollbackModelsTrait;
use Tests\DuskTestCase;
use Tests\Traits\MakesTestDigIdSessions;
use Tests\Traits\MakesTestFunds;
use Throwable;

class DigIdCompletionTest extends DuskTestCase
{
    use HasFrontendActions;
    use RollbackModelsTrait;
    use MakesTestDigIdSessions;
    use MakesTestFunds;
    use WithFaker;

    /**
     * @throws Throwable
     * @return void
     */
    public function testLoginCompletionAuthenticatesInitiatingBrowser(): void
    {
        $this->withLoginCompletionSession(function (
            Implementation $implementation,
            Identity $identity,
            DigIdSession $session,
            string $verifier,
            string $completionCode,
        ) {
            $this->browse(function (Browser $browser) use (
                $implementation,
                $identity,
                $session,
                $verifier,
                $completionCode,
            ) {
                $homeUrl = $this->prepareInitiatingBrowser($browser, $implementation, $session, $verifier);

                $browser->visit($this->completionUrl($session, $completionCode));
                $this->assertLoginCompleted($browser, $identity, $session, $homeUrl);

                $this->logout($browser);
            });
        });
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testCopiedCompletionLinkFailsInAnotherBrowserButSucceedsInInitiatingBrowser(): void
    {
        $this->withLoginCompletionSession(function (
            Implementation $implementation,
            Identity $identity,
            DigIdSession $session,
            string $verifier,
            string $completionCode,
        ) {
            $this->browse(function (Browser $browser, Browser $otherBrowser) use (
                $implementation,
                $identity,
                $session,
                $verifier,
                $completionCode,
            ) {
                $homeUrl = $this->prepareInitiatingBrowser($browser, $implementation, $session, $verifier);
                $completionUrl = $this->completionUrl($session, $completionCode);

                $otherBrowser->visit($completionUrl);
                $otherBrowser->waitUntil("window.location.href.includes('/error/digid_unknown_error')");
                $otherBrowser->waitFor('@btnStart')->assertMissing('@userProfile');
                $otherBrowser->assertScript('localStorage.getItem("active_account") === null');

                $otherBrowser->assertScript(
                    "sessionStorage.getItem('digid_browser:digid:$session->session_uid') === null",
                );

                $session->refresh();
                $this->assertSame(DigIdSession::STATE_AUTHORIZED, $session->state);
                $this->assertNull($session->identity_address);
                $this->assertFalse($session->meta['completion_consumed']);
                $this->assertSame(hash('sha256', $verifier), $session->meta['browser_challenge']);
                $this->assertSame(hash('sha256', $completionCode), $session->meta['completion_code_hash']);

                $browser->visit($completionUrl);
                $this->assertLoginCompleted($browser, $identity, $session, $homeUrl);
                $otherBrowser->assertScript('localStorage.getItem("active_account") === null');

                $this->logout($browser);
            });
        });
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFundVerificationAssignsBsnToInitiatingIdentity(): void
    {
        $implementation = Implementation::where('key', 'nijmegen')->firstOrFail();
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $bsn = (string) $this->randomFakeBsn();
        $verifier = bin2hex(random_bytes(32));
        $completionCode = bin2hex(random_bytes(32));

        ['fund' => $fund, 'session' => $session] = $this->makeFundVerificationFixture(
            $implementation,
            $identity,
            $bsn,
            $verifier,
            $completionCode,
        );

        $this->browse(function (Browser $browser) use (
            $implementation,
            $identity,
            $fund,
            $session,
            $bsn,
            $verifier,
            $completionCode,
        ) {
            $this->prepareInitiatingBrowser($browser, $implementation, $session, $verifier);
            $this->loginIdentity($browser, $identity);
            $this->assertIdentityAuthenticatedOnWebshop($browser, $identity);
            $this->assertNull($identity->refresh()->bsn);

            $accessToken = $browser->driver->executeScript('return localStorage.getItem("active_account");');

            $browser->visit($this->completionUrl($session, $completionCode));

            $this->assertFundApplicationOpened($browser, $fund);
            $this->assertIdentityAuthenticatedOnWebshop($browser, $identity);

            $this->assertSame(
                $accessToken,
                $browser->driver->executeScript('return localStorage.getItem("active_account");'),
            );

            $this->assertSame($bsn, $identity->refresh()->bsn);
            $this->assertCompletionConsumed($browser, $session);

            $this->logout($browser);
        });
    }

    /**
     * @param Implementation $implementation
     * @param Identity $identity
     * @param string $bsn
     * @param string $verifier
     * @param string $completionCode
     * @throws Throwable
     * @return array{fund: Fund, session: DigIdSession}
     */
    protected function makeFundVerificationFixture(
        Implementation $implementation,
        Identity $identity,
        string $bsn,
        string $verifier,
        string $completionCode,
    ): array {
        $organization = $implementation->organization;
        $implementationFields = $implementation->only(['digid_forus_api_url']);
        $organizationFields = $organization->only(['bsn_enabled']);

        $fund = $this->makeTestFund($organization, fundConfigsData: [
            'allow_fund_requests' => true,
            'allow_prevalidations' => true,
            'bsn_confirmation_time' => 900,
            'bsn_confirmation_api_time' => 900,
        ], implementation: $implementation);

        $session = null;

        $this->beforeApplicationDestroyed(function () use (
            $implementation,
            $organization,
            $implementationFields,
            $organizationFields,
            $fund,
            &$session,
        ) {
            $session?->forceDelete();
            $this->deleteFund($fund);
            $implementation->forceFill($implementationFields)->save();
            $organization->forceFill($organizationFields)->save();
        });

        $organization->forceFill(['bsn_enabled' => true])->save();

        $session = $this->makeAuthorizedDigIdSession(new DigIdSessionData(
            implementationId: $implementation->id,
            organizationId: $organization->id,
            connectionType: DigIdSession::CONNECTION_TYPE_SAML,
            clientType: Implementation::FRONTEND_WEBSHOP,
            identityAddress: $identity->address,
            sessionRequest: DigIdSession::SESSION_REQUEST_FUND_REQUEST,
            sessionFinalUrl: $fund->urlWebshop("/fondsen/$fund->id/activeer"),
            browserChallenge: hash('sha256', $verifier),
            fundId: $fund->id,
        ), $bsn, $completionCode);

        return compact('fund', 'session');
    }

    /**
     * @param callable(Implementation, Identity, DigIdSession, string, string): void $callback
     * @throws Throwable
     * @return void
     */
    protected function withLoginCompletionSession(callable $callback): void
    {
        $implementation = Implementation::where('key', 'nijmegen')->firstOrFail();
        $identity = $this->makeIdentity($this->makeUniqueEmail(), (string) $this->randomFakeBsn());
        $verifier = bin2hex(random_bytes(32));
        $completionCode = bin2hex(random_bytes(32));

        $session = $this->makeAuthorizedDigIdSession(new DigIdSessionData(
            implementationId: $implementation->id,
            organizationId: $implementation->organization_id,
            connectionType: DigIdSession::CONNECTION_TYPE_SAML,
            clientType: Implementation::FRONTEND_WEBSHOP,
            identityAddress: null,
            sessionRequest: DigIdSession::SESSION_REQUEST_AUTH,
            sessionFinalUrl: $implementation->urlWebshop(),
            browserChallenge: hash('sha256', $verifier),
        ), $identity->bsn, $completionCode);

        $funds = $implementation->funds;

        try {
            $this->rollbackModels(
                [
                    [$implementation, $implementation->only(['digid_forus_api_url'])],
                    ...$funds->map(fn (Fund $fund) => [$fund, $fund->only(['state'])])->all(),
                ],
                function () use ($callback, $implementation, $identity, $session, $verifier, $completionCode, $funds) {
                    $funds->each(fn (Fund $fund) => $fund->update(['state' => Fund::STATE_CLOSED]));

                    $callback($implementation, $identity, $session, $verifier, $completionCode);
                },
            );
        } finally {
            $session->forceDelete();
        }
    }

    /**
     * @param Browser $browser
     * @param Implementation $implementation
     * @param DigIdSession $session
     * @param string $verifier
     * @throws Throwable
     * @return string
     */
    protected function prepareInitiatingBrowser(
        Browser $browser,
        Implementation $implementation,
        DigIdSession $session,
        string $verifier,
    ): string {
        $browser->visit($implementation->urlWebshop())->waitFor('@headerTitle');
        $browser->script('localStorage.removeItem("active_account");');
        $browser->refresh()->waitFor('@btnStart');

        $apiOrigin = $browser->driver->executeScript(<<<'JS'
                const request = performance.getEntriesByType('resource').find((entry) => {
                    return new URL(entry.name).pathname.endsWith('/platform/config/webshop');
                });

                return new URL(request.name).origin;
            JS);

        $implementation->forceFill(['digid_forus_api_url' => $apiOrigin])->save();

        $browser->driver->executeScript('sessionStorage.setItem(arguments[0], arguments[1]);', [
            "digid_browser:digid:$session->session_uid", $verifier,
        ]);

        return $browser->driver->getCurrentURL();
    }

    /**
     * @param DigIdSession $session
     * @param string $completionCode
     * @return string
     */
    protected function completionUrl(DigIdSession $session, string $completionCode): string
    {
        return $session->implementation->urlWebshop('/digid-complete', [
            'transport' => 'digid',
            'session_uid' => $session->session_uid,
            'completion_code' => $completionCode,
        ]);
    }

    /**
     * @param Browser $browser
     * @param Identity $identity
     * @param DigIdSession $session
     * @param string $homeUrl
     * @throws Throwable
     * @return void
     */
    protected function assertLoginCompleted(
        Browser $browser,
        Identity $identity,
        DigIdSession $session,
        string $homeUrl,
    ): void {
        $browser->waitUsing(null, 100, fn () => $browser->driver->getCurrentURL() === $homeUrl);
        $browser->waitFor('#main-content');
        $this->assertIdentityAuthenticatedOnWebshop($browser, $identity);
        $this->assertCompletionConsumed($browser, $session);

        $this->assertSame($identity->address, $session->identity_address);
        $this->assertNull($session->meta['completion_code_hash']);
        $this->assertNull($session->meta['browser_challenge']);
    }

    /**
     * @param Browser $browser
     * @param Fund $fund
     * @throws Throwable
     * @return void
     */
    protected function assertFundApplicationOpened(Browser $browser, Fund $fund): void
    {
        $browser->waitFor('@fundRequestForm');
        $browser->assertScript("window.location.href.includes('/fondsen/$fund->id/aanvraag')");
    }

    /**
     * @param Browser $browser
     * @param DigIdSession $session
     * @return void
     */
    protected function assertCompletionConsumed(Browser $browser, DigIdSession $session): void
    {
        $browser->assertScript("sessionStorage.getItem('digid_browser:digid:$session->session_uid') === null");
        $this->assertTrue($session->refresh()->meta['completion_consumed']);
    }
}
