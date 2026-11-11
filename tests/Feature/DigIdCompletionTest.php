<?php

namespace Tests\Feature;

use App\Models\Identity;
use App\Models\IdentityProxy;
use App\Models\Implementation;
use App\Services\DigIdService\Models\DigIdSession;
use App\Services\DigIdService\Objects\DigidAuthResolveData;
use App\Services\DigIdService\Objects\DigIdSessionData;
use App\Services\DigIdService\Repositories\Interfaces\DigIdRepo;
use App\Services\DigIdService\TvsService;
use App\Services\SAML2Service\Responses\SamlArtifactResponse;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;
use Tests\Traits\MakesTestDigIdSessions;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Throwable;

class DigIdCompletionTest extends TestCase
{
    use DatabaseTransactions;
    use MakesTestDigIdSessions;
    use MakesTestFunds;
    use MakesTestOrganizations;

    /**
     * @return array<string, array{string}>
     */
    public static function connectionTypes(): array
    {
        return [
            'cgi' => [DigIdSession::CONNECTION_TYPE_CGI],
            'saml' => [DigIdSession::CONNECTION_TYPE_SAML],
            'tvs' => [DigIdSession::CONNECTION_TYPE_TVS],
        ];
    }

    /**
     * @param string $connectionType
     * @throws Throwable
     * @return void
     */
    #[DataProvider('connectionTypes')]
    public function testRejectsIncorrectBrowserVerifierWithoutConsumingCompletionCode(string $connectionType): void
    {
        $this->assertInvalidProofAllowsValidRetry($connectionType, 'browser_verifier');
    }

    /**
     * @param string $connectionType
     * @throws Throwable
     * @return void
     */
    #[DataProvider('connectionTypes')]
    public function testRejectsIncorrectCompletionCodeWithoutConsumingIt(string $connectionType): void
    {
        $this->assertInvalidProofAllowsValidRetry($connectionType, 'completion_code');
    }

    /**
     * @param string $connectionType
     * @throws Throwable
     * @return void
     */
    #[DataProvider('connectionTypes')]
    public function testRejectsFundCompletionByAnotherIdentityWithoutConsumingCompletion(string $connectionType): void
    {
        $this->assertFundCompletionRequiresInitiatingIdentity($connectionType, true);
    }

    /**
     * @param string $connectionType
     * @throws Throwable
     * @return void
     */
    #[DataProvider('connectionTypes')]
    public function testRejectsUnauthenticatedFundCompletionWithoutConsumingCompletion(string $connectionType): void
    {
        $this->assertFundCompletionRequiresInitiatingIdentity($connectionType, false);
    }

    /**
     * @param string $connectionType
     * @throws Throwable
     * @return void
     */
    #[DataProvider('connectionTypes')]
    public function testRejectsLoginCompletionAfterOriginalSessionExpires(string $connectionType): void
    {
        $this->assertExpiredCompletionRejected($connectionType, DigIdSession::SESSION_REQUEST_AUTH);
    }

    /**
     * @param string $connectionType
     * @throws Throwable
     * @return void
     */
    #[DataProvider('connectionTypes')]
    public function testRejectsFundCompletionAfterOriginalSessionExpires(string $connectionType): void
    {
        $this->assertExpiredCompletionRejected($connectionType, DigIdSession::SESSION_REQUEST_FUND_REQUEST);
    }

    /**
     * @param string $connectionType
     * @throws Throwable
     * @return void
     */
    #[DataProvider('connectionTypes')]
    public function testRejectsCompletionReplayWithoutIssuingAnotherCredential(string $connectionType): void
    {
        $this->assertCompletionReplayDoesNotIssueCredential($connectionType);
    }

    /**
     * @param string $connectionType
     * @throws Throwable
     * @return void
     */
    #[DataProvider('connectionTypes')]
    public function testRejectsWrongImplementationWithoutConsumingCompletion(string $connectionType): void
    {
        $this->assertWrongContextAllowsValidRetry($connectionType, 'implementation');
    }

    /**
     * @param string $connectionType
     * @throws Throwable
     * @return void
     */
    #[DataProvider('connectionTypes')]
    public function testRejectsWrongClientTypeWithoutConsumingCompletion(string $connectionType): void
    {
        $this->assertWrongContextAllowsValidRetry($connectionType, 'client_type');
    }

    /**
     * @param string $connectionType
     * @throws Throwable
     * @return void
     */
    #[DataProvider('connectionTypes')]
    public function testRejectsWrongTransportWithoutConsumingCompletion(string $connectionType): void
    {
        $this->assertWrongContextAllowsValidRetry($connectionType, 'transport');
    }

    /**
     * @param string $connectionType
     * @throws Throwable
     * @return void
     */
    #[DataProvider('connectionTypes')]
    public function testLoginCallbackDefersAccountCreationAndIssuesCompletionCodeOnce(string $connectionType): void
    {
        $this->assertCallbackDefersCompletionAndRejectsReplay($connectionType, DigIdSession::SESSION_REQUEST_AUTH);
    }

    /**
     * @param string $connectionType
     * @throws Throwable
     * @return void
     */
    #[DataProvider('connectionTypes')]
    public function testFundCallbackDefersBsnAssignmentAndIssuesCompletionCodeOnce(string $connectionType): void
    {
        $this->assertCallbackDefersCompletionAndRejectsReplay(
            $connectionType,
            DigIdSession::SESSION_REQUEST_FUND_REQUEST,
        );
    }

    /**
     * @param string $connectionType
     * @throws Throwable
     * @return void
     */
    #[DataProvider('connectionTypes')]
    public function testFundCompletionPreservesExistingBsnWhenVerifiedBsnDiffers(string $connectionType): void
    {
        $this->assertFundBsnConflictPreservesOwnership($connectionType, false);
    }

    /**
     * @param string $connectionType
     * @throws Throwable
     * @return void
     */
    #[DataProvider('connectionTypes')]
    public function testFundCompletionDoesNotAssignBsnOwnedByAnotherIdentity(string $connectionType): void
    {
        $this->assertFundBsnConflictPreservesOwnership($connectionType, true);
    }

    /**
     * @param string $connectionType
     * @throws Throwable
     * @return void
     */
    #[DataProvider('connectionTypes')]
    public function testLoginSessionWithoutBrowserChallengeCannotIssueCodeOrComplete(string $connectionType): void
    {
        $this->assertMissingBrowserChallengeRequiresRestart($connectionType, DigIdSession::SESSION_REQUEST_AUTH);
    }

    /**
     * @param string $connectionType
     * @throws Throwable
     * @return void
     */
    #[DataProvider('connectionTypes')]
    public function testFundSessionWithoutBrowserChallengeCannotIssueCodeOrComplete(string $connectionType): void
    {
        $this->assertMissingBrowserChallengeRequiresRestart(
            $connectionType,
            DigIdSession::SESSION_REQUEST_FUND_REQUEST,
        );
    }

    /**
     * @param string $connectionType
     * @throws Throwable
     * @return array{
     *     identity: Identity, session: DigIdSession, transport: string,
     *     headers: array<string, string>, proof: array<string, string>
     * }
     */
    protected function makeLoginCompletionFixture(string $connectionType): array
    {
        $bsn = (string) $this->randomFakeBsn();
        $identity = $this->makeIdentity($this->makeUniqueEmail(), $bsn);
        $organization = $this->makeTestOrganization($this->makeIdentity(), ['bsn_enabled' => true]);

        $implementation = $this->makeTestImplementation($organization, [
            'digid_forus_api_url' => url('/'),
        ]);

        $verifier = bin2hex(random_bytes(32));
        $completionCode = bin2hex(random_bytes(32));

        $session = $this->makeAuthorizedDigIdSession(new DigIdSessionData(
            implementationId: $implementation->id,
            organizationId: $organization->id,
            connectionType: $connectionType,
            clientType: 'webshop',
            identityAddress: null,
            sessionRequest: DigIdSession::SESSION_REQUEST_AUTH,
            sessionFinalUrl: 'https://webshop.example.test',
            browserChallenge: hash('sha256', $verifier),
            dvEntityId: $connectionType === DigIdSession::CONNECTION_TYPE_TVS ? 'https://dv.example.test' : null,
            serviceUuid: $connectionType === DigIdSession::CONNECTION_TYPE_TVS ? fake()->uuid() : null,
        ), $bsn, $completionCode);

        $transport = $connectionType === DigIdSession::CONNECTION_TYPE_TVS ? 'tvs' : 'digid';
        $headers = ['Client-Key' => $implementation->key];

        $proof = [
            'session_uid' => $session->session_uid,
            'browser_verifier' => $verifier,
            'completion_code' => $completionCode,
        ];

        return compact('identity', 'session', 'transport', 'headers', 'proof');
    }

    /**
     * @param string $connectionType
     * @param string $invalidField
     * @throws Throwable
     * @return void
     */
    protected function assertInvalidProofAllowsValidRetry(string $connectionType, string $invalidField): void
    {
        ['identity' => $identity, 'transport' => $transport, 'headers' => $headers, 'proof' => $proof] =
            $this->makeLoginCompletionFixture($connectionType);

        $proxyCount = IdentityProxy::withTrashed()->count();

        $this->apiCompleteDigIdRequest($transport, [
            ...$proof,
            $invalidField => str_repeat('0', 64),
        ], $headers)->assertForbidden()->assertHeader('Error-Code', 'digid_unknown_error');

        $this->assertSame($proxyCount, IdentityProxy::withTrashed()->count());
        $this->assertLoginCompleted($this->apiCompleteDigIdRequest($transport, $proof, $headers), $identity);
    }

    /**
     * @param string $connectionType
     * @throws Throwable
     * @return void
     */
    protected function assertCompletionReplayDoesNotIssueCredential(string $connectionType): void
    {
        $this->freezeTime();

        ['identity' => $identity, 'transport' => $transport, 'headers' => $headers, 'proof' => $proof] =
            $this->makeLoginCompletionFixture($connectionType);

        $this->assertLoginCompleted($this->apiCompleteDigIdRequest($transport, $proof, $headers), $identity);
        $proxyCount = IdentityProxy::withTrashed()->count();

        $this->apiCompleteDigIdRequest($transport, $proof, $headers)
            ->assertForbidden()
            ->assertHeader('Error-Code', 'digid_unknown_error');

        $this->assertSame($proxyCount, IdentityProxy::withTrashed()->count());
    }

    /**
     * @param string $connectionType
     * @param string $wrongContext
     * @throws Throwable
     * @return void
     */
    protected function assertWrongContextAllowsValidRetry(string $connectionType, string $wrongContext): void
    {
        ['identity' => $identity, 'transport' => $transport, 'headers' => $headers, 'proof' => $proof] =
            $this->makeLoginCompletionFixture($connectionType);

        [$wrongTransport, $wrongHeaders] = match ($wrongContext) {
            'implementation' => [$transport, [...$headers, 'Client-Key' => Implementation::general()->key]],
            'client_type' => [$transport, [...$headers, 'Client-Type' => Implementation::FRONTEND_SPONSOR_DASHBOARD]],
            'transport' => [$transport === 'tvs' ? 'digid' : 'tvs', $headers],
        };

        $proxyCount = IdentityProxy::withTrashed()->count();

        $this->apiCompleteDigIdRequest($wrongTransport, $proof, $wrongHeaders)
            ->assertForbidden()
            ->assertHeader('Error-Code', 'digid_unknown_error');

        $this->assertSame($proxyCount, IdentityProxy::withTrashed()->count());
        $this->assertLoginCompleted($this->apiCompleteDigIdRequest($transport, $proof, $headers), $identity);
    }

    /**
     * @param TestResponse $response
     * @param Identity $identity
     * @return void
     */
    protected function assertLoginCompleted(TestResponse $response, Identity $identity): void
    {
        $response->assertOk();
        $proxy = $identity->proxies()->where('type', 'short_token')->sole();

        $response->assertExactJson([
            'redirect_url' => 'https://webshop.example.test/auth-link?' . http_build_query([
                'token' => $proxy->exchange_token,
            ]),
        ]);

        $this->assertSame(IdentityProxy::STATE_ACTIVE, $proxy->state);
        $this->assertNotEmpty($proxy->access_token);
    }

    /**
     * @param string $connectionType
     * @param Identity $identity
     * @param string $bsn
     * @throws Throwable
     * @return array{
     *     session: DigIdSession, transport: string, headers: array<string, string>,
     *     proof: array<string, string>, finalUrl: string
     * }
     */
    protected function makeFundCompletionFixture(
        string $connectionType,
        Identity $identity,
        string $bsn,
    ): array {
        $organization = $this->makeTestOrganization($this->makeIdentity(), ['bsn_enabled' => true]);

        $implementation = $this->makeTestImplementation($organization, [
            'digid_forus_api_url' => url('/'),
        ]);

        $fund = $this->makeTestFund($organization, implementation: $implementation);
        $verifier = bin2hex(random_bytes(32));
        $completionCode = bin2hex(random_bytes(32));
        $finalUrl = $fund->urlWebshop("/fondsen/$fund->id/activeer");

        $session = $this->makeAuthorizedDigIdSession(new DigIdSessionData(
            implementationId: $implementation->id,
            organizationId: $organization->id,
            connectionType: $connectionType,
            clientType: 'webshop',
            identityAddress: $identity->address,
            sessionRequest: DigIdSession::SESSION_REQUEST_FUND_REQUEST,
            sessionFinalUrl: $finalUrl,
            browserChallenge: hash('sha256', $verifier),
            fundId: $fund->id,
            dvEntityId: $connectionType === DigIdSession::CONNECTION_TYPE_TVS ? 'https://dv.example.test' : null,
            serviceUuid: $connectionType === DigIdSession::CONNECTION_TYPE_TVS ? fake()->uuid() : null,
        ), $bsn, $completionCode);

        $transport = $connectionType === DigIdSession::CONNECTION_TYPE_TVS ? 'tvs' : 'digid';
        $headers = ['Client-Key' => $implementation->key];

        $proof = [
            'session_uid' => $session->session_uid,
            'browser_verifier' => $verifier,
            'completion_code' => $completionCode,
        ];

        return compact('session', 'transport', 'headers', 'proof', 'finalUrl');
    }

    /**
     * @param string $connectionType
     * @param bool $authenticatedAsOtherIdentity
     * @throws Throwable
     * @return void
     */
    protected function assertFundCompletionRequiresInitiatingIdentity(
        string $connectionType,
        bool $authenticatedAsOtherIdentity,
    ): void {
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $otherIdentity = $authenticatedAsOtherIdentity ? $this->makeIdentity($this->makeUniqueEmail()) : null;
        $bsn = (string) $this->randomFakeBsn();

        ['transport' => $transport, 'headers' => $headers, 'proof' => $proof, 'finalUrl' => $finalUrl] =
            $this->makeFundCompletionFixture($connectionType, $identity, $bsn);

        $this->apiCompleteDigIdRequest($transport, $proof, $this->makeApiHeaders($otherIdentity ?? false, $headers))
            ->assertForbidden()
            ->assertHeader('Error-Code', 'digid_unknown_error');

        $this->assertNull($identity->refresh()->bsn);

        if ($otherIdentity) {
            $this->assertNull($otherIdentity->refresh()->bsn);
        }

        $this->apiCompleteDigIdRequest($transport, $proof, $this->makeApiHeaders($identity, $headers))
            ->assertOk()
            ->assertExactJson([
                'redirect_url' => url_extend_get_params($finalUrl, ['digid_success' => 'signed_up']),
            ]);

        $this->assertSame($bsn, $identity->refresh()->bsn);
    }

    /**
     * @param string $connectionType
     * @param bool $bsnBelongsToOtherIdentity
     * @throws Throwable
     * @return void
     */
    protected function assertFundBsnConflictPreservesOwnership(
        string $connectionType,
        bool $bsnBelongsToOtherIdentity,
    ): void {
        $existingBsn = $bsnBelongsToOtherIdentity ? null : (string) $this->randomFakeBsn();
        $identity = $this->makeIdentity($this->makeUniqueEmail(), $existingBsn);
        $bsn = (string) $this->randomFakeBsn();
        $otherIdentity = $bsnBelongsToOtherIdentity ? $this->makeIdentity($this->makeUniqueEmail(), $bsn) : null;

        ['transport' => $transport, 'headers' => $headers, 'proof' => $proof, 'finalUrl' => $finalUrl] =
            $this->makeFundCompletionFixture($connectionType, $identity, $bsn);

        $this->apiCompleteDigIdRequest($transport, $proof, $this->makeApiHeaders($identity, $headers))
            ->assertOk()
            ->assertExactJson([
                'redirect_url' => url_extend_get_params($finalUrl, [
                    'digid_error' => $bsnBelongsToOtherIdentity ? 'uid_used' : 'uid_dont_match',
                ]),
            ]);

        $this->assertSame($existingBsn, $identity->refresh()->bsn);
        $this->assertSame($otherIdentity?->address, Identity::findByBsn($bsn)?->address);
    }

    /**
     * @param string $connectionType
     * @param string $sessionRequest
     * @throws Throwable
     * @return void
     */
    protected function assertExpiredCompletionRejected(string $connectionType, string $sessionRequest): void
    {
        $this->freezeTime();

        $isFundRequest = $sessionRequest === DigIdSession::SESSION_REQUEST_FUND_REQUEST;

        if ($isFundRequest) {
            $identity = $this->makeIdentity($this->makeUniqueEmail());

            ['session' => $session, 'transport' => $transport, 'headers' => $headers, 'proof' => $proof] =
                $this->makeFundCompletionFixture($connectionType, $identity, (string) $this->randomFakeBsn());

            $headers = $this->makeApiHeaders($identity, $headers);
        } else {
            ['session' => $session, 'transport' => $transport, 'headers' => $headers, 'proof' => $proof] =
                $this->makeLoginCompletionFixture($connectionType);
        }

        $session->forceFill(['created_at' => now()->subMinutes(9)])->save();
        $proxyCount = IdentityProxy::withTrashed()->count();

        $this->travelTo($session->created_at->copy()->addMinutes(10)->addSecond());

        $this->apiCompleteDigIdRequest($transport, $proof, $headers)
            ->assertForbidden()
            ->assertHeader('Error-Code', 'digid_unknown_error');

        if ($isFundRequest) {
            $this->assertNull($identity->refresh()->bsn);
        } else {
            $this->assertSame($proxyCount, IdentityProxy::withTrashed()->count());
        }
    }

    /**
     * @param string $connectionType
     * @param string $sessionRequest
     * @throws Throwable
     * @return void
     */
    protected function assertCallbackDefersCompletionAndRejectsReplay(
        string $connectionType,
        string $sessionRequest,
    ): void {
        $isTvs = $connectionType === DigIdSession::CONNECTION_TYPE_TVS;
        $isFundRequest = $sessionRequest === DigIdSession::SESSION_REQUEST_FUND_REQUEST;
        $identity = $isFundRequest ? $this->makeIdentity($this->makeUniqueEmail()) : null;
        $organization = $this->makeTestOrganization($this->makeIdentity(), ['bsn_enabled' => true]);

        $implementation = $this->makeTestImplementation($organization, [
            'digid_forus_api_url' => url('/'),
            'url_webshop' => 'https://initiating.example.test',
        ]);

        $implementation->forceFill(['digid_sign_up_allowed' => true])->save();

        $fund = $isFundRequest ? $this->makeTestFund($organization, implementation: $this->makeTestImplementation(
            $organization,
            ['url_webshop' => 'https://destination.example.test'],
        )) : null;

        $bsn = (string) $this->randomFakeBsn();
        $verifier = bin2hex(random_bytes(32));
        $finalUrl = $fund ? $fund->urlWebshop("/fondsen/$fund->id/activeer") : 'https://webshop.example.test';

        $session = DigIdSession::createSession(new DigIdSessionData(
            implementationId: $implementation->id,
            organizationId: $organization->id,
            connectionType: $connectionType,
            clientType: 'webshop',
            identityAddress: $identity?->address,
            sessionRequest: $sessionRequest,
            sessionFinalUrl: $finalUrl,
            browserChallenge: hash('sha256', $verifier),
            fundId: $fund?->id,
            dvEntityId: $isTvs ? 'https://dv.example.test' : null,
            serviceUuid: $isTvs ? fake()->uuid() : null,
        ));

        $session->update([
            'state' => DigIdSession::STATE_PENDING_AUTH,
            'digid_rid' => $isTvs ? null : 'testrequestid',
        ]);

        $this->stubCallbackProvider($session, $bsn);

        $headers = $this->makeApiHeaders($identity ?? false, ['Client-Key' => $implementation->key]);
        $callbackData = $isTvs ? ['SAMLart' => 'test-artifact', 'RelayState' => $session->session_uid] : [];
        $identityCount = Identity::count();
        $proxyCount = IdentityProxy::withTrashed()->count();

        $response = $this->apiResolveDigIdRequest($session, $callbackData, $headers);
        parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY) ?: '', $completion);

        $this->assertMatchesRegularExpression('/\A[a-f0-9]{64}\z/', $completion['completion_code'] ?? '');

        $response->assertRedirect($implementation->urlWebshop('/digid-complete', [
            'transport' => $isTvs ? 'tvs' : 'digid',
            'session_uid' => $session->session_uid,
            'completion_code' => $completion['completion_code'],
        ]));

        $this->assertSame($identityCount, Identity::count());
        $this->assertSame($proxyCount, IdentityProxy::withTrashed()->count());

        if ($identity) {
            $this->assertNull($identity->refresh()->bsn);
        }

        $repeatedResponse = $this->apiResolveDigIdRequest($session, $callbackData, $headers);

        if ($isTvs) {
            $repeatedResponse->assertRedirect(url_extend_get_params($finalUrl, ['digid_error' => 'unknown_error']));
        } else {
            $repeatedResponse->assertNotFound();
            $repeatedResponse->assertHeaderMissing('Location');
        }

        $response = $this->apiCompleteDigIdRequest($isTvs ? 'tvs' : 'digid', [
            'session_uid' => $session->session_uid,
            'browser_verifier' => $verifier,
            'completion_code' => $completion['completion_code'],
        ], $headers);

        if ($identity) {
            $response->assertOk()->assertExactJson([
                'redirect_url' => url_extend_get_params($finalUrl, ['digid_success' => 'signed_up']),
            ]);

            $this->assertSame($bsn, $identity->refresh()->bsn);
        } else {
            $identity = Identity::findByBsn($bsn);
            $this->assertNotNull($identity);
            $this->assertLoginCompleted($response, $identity);
        }
    }

    /**
     * @param string $connectionType
     * @param string $sessionRequest
     * @throws Throwable
     * @return void
     */
    protected function assertMissingBrowserChallengeRequiresRestart(
        string $connectionType,
        string $sessionRequest,
    ): void {
        $isFundRequest = $sessionRequest === DigIdSession::SESSION_REQUEST_FUND_REQUEST;

        if ($isFundRequest) {
            $identity = $this->makeIdentity($this->makeUniqueEmail());
            $bsn = (string) $this->randomFakeBsn();

            ['session' => $session, 'transport' => $transport, 'headers' => $headers, 'proof' => $proof] =
                $this->makeFundCompletionFixture($connectionType, $identity, $bsn);

            $headers = $this->makeApiHeaders($identity, $headers);
        } else {
            ['session' => $session, 'transport' => $transport, 'headers' => $headers, 'proof' => $proof] =
                $this->makeLoginCompletionFixture($connectionType);

            $bsn = $session->digid_uid;
        }

        $meta = $session->meta;
        unset($meta['browser_challenge'], $meta['completion_code_hash'], $meta['completion_consumed']);

        $session->update([
            'state' => DigIdSession::STATE_PENDING_AUTH,
            'digid_uid' => null,
            'digid_rid' => $session->isConnectionTypeTvs() ? null : 'testrequestid',
            'meta' => $meta,
        ]);

        $this->stubCallbackProvider($session, $bsn, expectProviderResolution: false);
        $proxyCount = IdentityProxy::withTrashed()->count();

        $callbackData = $session->isConnectionTypeTvs()
            ? ['SAMLart' => 'test-artifact', 'RelayState' => $session->session_uid]
            : [];

        $this->apiResolveDigIdRequest($session, $callbackData, $headers)
            ->assertRedirect(url_extend_get_params($session->session_final_url, ['digid_error' => 'unknown_error']));

        $this->assertNull($session->refresh()->meta['completion_code_hash'] ?? null);

        $session->update([
            'state' => DigIdSession::STATE_AUTHORIZED,
            'digid_uid' => $bsn,
            'meta' => [
                ...$meta,
                'completion_code_hash' => hash('sha256', $proof['completion_code']),
                'completion_consumed' => false,
            ],
        ]);

        $this->apiCompleteDigIdRequest($transport, $proof, $headers)
            ->assertForbidden()
            ->assertHeader('Error-Code', 'digid_unknown_error');

        $this->assertSame($proxyCount, IdentityProxy::withTrashed()->count());

        if ($isFundRequest) {
            $this->assertNull($identity->refresh()->bsn);
        }
    }

    /**
     * @param DigIdSession $session
     * @param string $bsn
     * @param bool $expectProviderResolution
     * @return void
     */
    protected function stubCallbackProvider(
        DigIdSession $session,
        string $bsn,
        bool $expectProviderResolution = true,
    ): void {
        $resolution = $this->mock(DigIdRepo::class)->shouldReceive('resolveResponse')
            ->andReturn(new DigidAuthResolveData($bsn));

        if ($expectProviderResolution) {
            $resolution->once();
        }

        $model = new class () extends DigIdSession {
            /**
             * @param array $tvsConfig
             * @return DigIdRepo
             */
            protected function getDigid(array $tvsConfig = []): DigIdRepo
            {
                return resolve(DigIdRepo::class);
            }
        };

        if ($session->isConnectionTypeTvs()) {
            $service = $this->partialMock(TvsService::class);

            $service->shouldReceive('resolveResponseFromRequest')->andReturn([
                'session' => $model->newFromBuilder($session->getAttributes()),
                'response' => Mockery::mock(SamlArtifactResponse::class),
            ]);
        } else {
            $binding = Route::getBindingCallback('digid_session_uid');

            Route::bind('digid_session_uid', fn ($value, $route) => $model->newFromBuilder(
                $binding($value, $route)->getAttributes(),
            ));
        }
    }
}
