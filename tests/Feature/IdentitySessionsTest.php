<?php

namespace Tests\Feature;

use App\Services\MailDatabaseLoggerService\Traits\AssertsSentEmails;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Arr;
use Tests\TestCase;

class IdentitySessionsTest extends TestCase
{
    use AssertsSentEmails;
    use DatabaseTransactions;

    public function testIdentitySessionDates(): void
    {
        // Set up the identity and initial variables.
        // Simulate 6 sessions starting at different times.
        $identity = $this->makeIdentity();
        $totalSessions = 6;
        $currentDate = now();

        // For tracking session IDs we've already seen.
        $UIDs = [];

        for ($i = 0; $i < $totalSessions; $i++) {
            // Calculate the start date for the session.
            // We start at the beginning of the year and add months with each iteration.
            $date = $currentDate->clone()->startOfYear()->addMonths($i)->startOfMonth()->startOfDay();

            // Create a proxy for our identity and set up headers for the API request.
            $identityProxy = $this->makeIdentityProxy($identity);
            $identityHeaders = $this->makeApiHeaders($identityProxy);

            // Simulate the first request at the calculated start date.
            $this->travelTo($date);

            // Make an API call to get the sessions for the identity.
            $sessions = $this
                ->getJson('/api/v1/identity/sessions', $identityHeaders)
                ->assertSuccessful()
                ->json('data');

            // Find the new session that hasn't been recorded yet.
            $sessionsNew = collect($sessions)->whereNotIn('uid', $UIDs);
            $session = $sessionsNew->first();

            // Check that the number of sessions is correct.
            // Also, for the first call, the first request and last request times should match the current date.
            $this->assertCount($i + 1, $sessions);
            $this->assertCount(1, $sessionsNew);
            $this->assertEquals(Arr::get($session, 'first_request.created_at'), Arr::get($session, 'created_at'));
            $this->assertEquals(Arr::get($session, 'first_request.created_at'), $date->format('Y-m-d H:i:s'));
            $this->assertEquals(Arr::get($session, 'last_request.created_at'), $date->format('Y-m-d H:i:s'));

            // Now, simulate a follow-up request by moving forward two months in time.
            $newDate = $date->clone()->addMonths(2);
            $this->travelTo($newDate);

            // Make another API call after the time jump.
            $sessions = $this->getJson('/api/v1/identity/sessions', $identityHeaders)
                ->assertSuccessful()
                ->json('data');

            // Again, find the new session that was just updated.
            $sessionsNew = collect($sessions)->whereNotIn('uid', $UIDs);
            $session = $sessionsNew->first();

            // Check that the overall session count hasn't changed and that the session's times are updated correctly.
            // The first request time remains the same, but the last request time should now be the new date.
            $this->assertCount($i + 1, $sessions);
            $this->assertCount(1, $sessionsNew);
            $this->assertEquals(Arr::get($session, 'first_request.created_at'), Arr::get($session, 'created_at'));
            $this->assertEquals(Arr::get($session, 'first_request.created_at'), $date->format('Y-m-d H:i:s'));
            $this->assertEquals(Arr::get($session, 'last_request.created_at'), $newDate->format('Y-m-d H:i:s'));

            // Save the unique ID of this session so we don't re-check it in later iterations.
            $UIDs[] = $session['uid'];
        }
    }
}
