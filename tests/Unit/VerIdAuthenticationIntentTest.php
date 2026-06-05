<?php

namespace Tests\Unit;

use App\Services\OpenIdService\VerId\VerIdAuthenticationIntent;
use App\Services\OpenIdService\VerId\VerIdAuthenticationIntentResponse;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VerIdAuthenticationIntentTest extends TestCase
{
    /**
     * @return void
     */
    public function testSendPostsAuthenticationIntentRequest(): void
    {
        Http::fake([
            'https://issuer.example/intent' => Http::response([
                'intent_id' => 'intent-123',
            ]),
        ]);

        $response = (new VerIdAuthenticationIntent(
            $this->makeConfig(),
            'code-challenge',
            'https://issuer.example/intent',
        ))->send();

        $this->assertTrue($response->successful());
        $this->assertSame('intent-123', $response->id());

        Http::assertSent(fn (Request $request) => $request->url() === 'https://issuer.example/intent' &&
            $request->method() === 'POST' &&
            $request->hasHeader('Authorization', 'Basic ' . base64_encode('verid-client:verid-secret')) &&
            $request->data() === [
                'scope' => 'openid',
                'client_id' => 'verid-client',
                'code_challenge' => 'code-challenge',
                'brandUuid' => 'brand-123',
            ]);
    }

    /**
     * @return void
     */
    public function testSendFailsWhenIntentEndpointIsMissing(): void
    {
        Http::fake();

        $response = (new VerIdAuthenticationIntent($this->makeConfig(), 'code-challenge', null))->send();

        $this->assertFalse($response->successful());
        $this->assertSame(VerIdAuthenticationIntentResponse::ERROR_MISSING_INTENT_ENDPOINT, $response->errorCode());

        Http::assertNothingSent();
    }

    public function testSendFailsWhenHttpRequestFails(): void
    {
        Http::fake([
            'https://issuer.example/intent' => Http::response([
                'error' => 'invalid_request',
                'error_description' => 'Invalid brand.',
            ], 400),
        ]);

        $response = (new VerIdAuthenticationIntent(
            $this->makeConfig(),
            'code-challenge',
            'https://issuer.example/intent',
        ))->send();

        $this->assertFalse($response->successful());
        $this->assertSame(400, $response->status());
        $this->assertSame('invalid_request', $response->errorCode());
        $this->assertSame('Invalid brand.', $response->errorMessage());
    }

    /**
     * @return void
     */
    public function testSendFailsWhenHttpRequestThrows(): void
    {
        Http::fake([
            'https://issuer.example/intent' => Http::failedConnection('Connection failed.'),
        ]);

        $response = (new VerIdAuthenticationIntent(
            $this->makeConfig(),
            'code-challenge',
            'https://issuer.example/intent',
        ))->send();

        $this->assertFalse($response->successful());
        $this->assertNull($response->status());
        $this->assertSame(VerIdAuthenticationIntentResponse::ERROR_REQUEST_EXCEPTION, $response->errorCode());
        $this->assertNotEmpty($response->errorMessage());
        $this->assertInstanceOf(ConnectionException::class, $response->exception());

        $logContext = $response->logContext();

        $this->assertSame(VerIdAuthenticationIntentResponse::ERROR_REQUEST_EXCEPTION, $logContext['error_code']);
        $this->assertSame(ConnectionException::class, $logContext['exception_class']);
        $this->assertArrayHasKey('error_message', $logContext);

        $this->assertArrayHasKey('exception_message', $response->logContext(includeExceptionMessages: true));
    }

    /**
     * @return void
     */
    public function testSendFailsWhenIntentIdIsMissing(): void
    {
        Http::fake([
            'https://issuer.example/intent' => Http::response([
                'status' => 'ok',
            ]),
        ]);

        $response = (new VerIdAuthenticationIntent(
            $this->makeConfig(),
            'code-challenge',
            'https://issuer.example/intent',
        ))->send();

        $this->assertFalse($response->successful());
        $this->assertSame(200, $response->status());
        $this->assertSame(VerIdAuthenticationIntentResponse::ERROR_MISSING_INTENT_ID, $response->errorCode());
    }

    /**
     * @param array $overrides
     * @return array
     */
    protected function makeConfig(array $overrides = []): array
    {
        return [
            'client_id' => 'verid-client',
            'client_secret' => 'verid-secret',
            'authentication_intent' => [
                'enabled' => true,
                'brand_uuid' => 'brand-123',
            ],
            ...$overrides,
        ];
    }
}
