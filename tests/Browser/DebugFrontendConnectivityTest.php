<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;
use Illuminate\Support\Facades\Log;
use App\Models\Implementation;
use App\Models\Employee;
use App\Models\Identity;
use App\Models\Organization;
use App\Models\Role;
use App\Services\MailDatabaseLoggerService\Traits\AssertsSentEmails;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Facebook\WebDriver\Exception\TimeOutException;
use Tests\Browser\Traits\HasFrontendActions;
use Tests\Traits\MakesTestIdentities;

class DebugFrontendConnectivityTest extends DuskTestCase
{
    /**
     * Test connectivity and debug frontend.
     */
    public function testDebugFrontendConnectivity()
    {

        $implementation = Implementation::byKey('nijmegen');
        $this->assertNotNull($implementation);
        $this->assertNotNull($implementation->organization);

        $frontendUrls = [
            'WEBSHOP_GENERAL_URL' => $implementation->urlWebshop(),
            'DASHBOARD_SPONSOR_URL'    => $implementation->urlSponsorDashboard(),
            'DASHBOARD_PROVIDER_URL'   => $implementation->urlProviderDashboard(),
            'DASHBOARD_VALIDATOR_URL'  => $implementation->urlValidatorDashboard(),
        ];

        foreach ($frontendUrls as $key => $url) {
            $this->browse(function (Browser $browser) use ($url, $key) {
                Log::info("Testing URL: $url");
                try {
                    $browser->visit($url);
                    Log::info("$key is accessible");
                } catch (\Exception $e) {
                    Log::error("Error accessing $url: " . $e->getMessage());
                    $browser->screenshot("error_$key");
                }
            });
        }
    }
}
