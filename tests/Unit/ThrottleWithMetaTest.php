<?php

namespace Tests\Unit;

use App\Exceptions\AuthorizationJsonException;
use App\Traits\ThrottleWithMeta;
use Illuminate\Http\Request;
use Tests\TestCase;

class ThrottleWithMetaTest extends TestCase
{
    /**
     * @throws AuthorizationJsonException
     * @return void
     */
    public function testThrottleWithMinuteDecay(): void
    {
        $subject = new ThrottleWithMetaStub(maxAttempts: 1, decayMinutes: 1.0);
        $request = Request::create('/', 'POST', [], [], [], [
            'REMOTE_ADDR' => '10.0.0.1',
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $subject->attempt($request);

        try {
            $subject->attempt($request);
            $this->fail('Expected AuthorizationJsonException to be thrown.');
        } catch (AuthorizationJsonException $exception) {
            $payload = json_decode($exception->getMessage(), true);
            $meta = $payload['meta'] ?? [];

            $this->assertEqualsWithDelta(1.0, $meta['decay_minutes'], 0.0001);
            $this->assertEqualsWithDelta(60.0, $meta['decay_seconds'], 0.0001);
        }
    }

    /**
     * @throws AuthorizationJsonException
     * @return void
     */
    public function testThrottleWithSecondDecay(): void
    {
        $decayMinutes = 5 / 60;
        $subject = new ThrottleWithMetaStub(maxAttempts: 1, decayMinutes: $decayMinutes);
        $request = Request::create('/', 'POST', [], [], [], [
            'REMOTE_ADDR' => '10.0.0.2',
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $subject->attempt($request);

        try {
            $subject->attempt($request);
            $this->fail('Expected AuthorizationJsonException to be thrown.');
        } catch (AuthorizationJsonException $exception) {
            $payload = json_decode($exception->getMessage(), true);
            $meta = $payload['meta'] ?? [];

            $this->assertEqualsWithDelta($decayMinutes, $meta['decay_minutes'], 0.0001);
            $this->assertEqualsWithDelta(5.0, $meta['decay_seconds'], 0.0001);
        }
    }
}

class ThrottleWithMetaStub
{
    use ThrottleWithMeta;

    public function __construct(int $maxAttempts, float $decayMinutes)
    {
        $this->maxAttempts = $maxAttempts;
        $this->decayMinutes = $decayMinutes;
    }

    /**
     * @param Request $request
     * @throws AuthorizationJsonException
     * @return void
     */
    public function attempt(Request $request): void
    {
        $this->throttleWithKey('to_many_attempts', $request, 'make_transaction');
    }
}
