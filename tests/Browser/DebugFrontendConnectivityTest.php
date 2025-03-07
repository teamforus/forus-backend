<?php

namespace Tests\Browser;

use App\Models\Implementation;
use Illuminate\Support\Facades\Log;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;
use Throwable;

class DebugFrontendConnectivityTest extends DuskTestCase
{
    /**
     * Test connectivity and debug frontend.
     *
     * @throws Throwable
     * @return void
     */
    public function testDebugFrontendConnectivity()
    {
        $implementation = Implementation::byKey('nijmegen');
        $this->assertNotNull($implementation);
        $this->assertNotNull($implementation->organization);

        $frontendUrls = [
            'WEBSHOP_GENERAL_URL' => $implementation->urlWebshop(),
            'DASHBOARD_SPONSOR_URL' => $implementation->urlSponsorDashboard(),
            'DASHBOARD_PROVIDER_URL' => $implementation->urlProviderDashboard(),
            'DASHBOARD_VALIDATOR_URL' => $implementation->urlValidatorDashboard(),
        ];

        foreach ($frontendUrls as $key => $url) {
            $this->browse(function (Browser $browser) use ($url, $key) {
                Log::info("Testing URL: $url");

                try {
                    $browser->visit($url);
                    Log::info("$key is accessible");
                } catch (Throwable $e) {
                    Log::error("Error accessing $url: " . $e->getMessage());
                    $browser->screenshot("error_$key");
                }
            });
        }
    }
}
