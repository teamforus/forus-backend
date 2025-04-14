<?php

namespace Tests\Unit;

use App\Http\Requests\BaseFormRequest;
use App\Models\Fund;
use App\Traits\DoesTesting;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\CreatesApplication;
use Tests\TestCase;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestIdentities;
use Tests\Traits\MakesTestOrganizations;

class TranslationLimitsTest extends TestCase
{
    use DoesTesting;
    use MakesTestFunds;
    use CreatesApplication;
    use MakesTestIdentities;
    use DatabaseTransactions;
    use MakesTestOrganizations;

    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function testDailyWeeklyAndMonthlyLimits(): void
    {
        $date = Carbon::now()->startOfMonth()->addWeek()->startOfWeek();
        $this->travelTo($date);

        /** @var Fund $fund */
        /** @var BaseFormRequest $request */
        $data = $this->makeTestData(5, 10, 15);
        $fund = $data['fund'];
        $request = $data['request'];

        // Assert exceeding daily limit prevents further translations
        $this->assertTranslation($fund, $request, str_repeat('A', 6), false);
        $this->assertTranslation($fund, $request, str_repeat('A', 5), true);
        $this->assertTranslation($fund, $request, str_repeat('B', 5), false);

        // Assert next day limits are reset
        $this->travelTo($date->addDay());
        $this->assertTranslation($fund, $request, str_repeat('C', 6), false);
        $this->assertTranslation($fund, $request, str_repeat('C', 5), true);

        // Assert third day of the week we hit weekly limit
        $this->travelTo($date->addDay());
        $this->assertTranslation($fund, $request, str_repeat('D', 6), false);
        $this->assertTranslation($fund, $request, str_repeat('D', 5), false);

        // Assert next week we tap into next week limit
        $this->travelTo($date->addWeek());
        $this->assertTranslation($fund, $request, str_repeat('E', 6), false);
        $this->assertTranslation($fund, $request, str_repeat('E', 5), true);
        $this->assertTranslation($fund, $request, str_repeat('F', 5), false);

        // Assert third week we hit monthly limit
        $this->travelTo($date->addWeek());
        $this->assertTranslation($fund, $request, str_repeat('G', 6), false);
        $this->assertTranslation($fund, $request, str_repeat('G', 5), false);
    }

    /**
     * @param Fund $fund
     * @param BaseFormRequest $request
     * @param string $name
     * @param bool $assertTranslated
     * @return void
     */
    protected function assertTranslation(
        Fund $fund,
        BaseFormRequest $request,
        string $name,
        bool $assertTranslated,
    ): void {
        $fund->forceFill(['name' => $name])->save();
        $data = $fund->translateColumns($fund->only('name'), 'nl', 'de', $request);

        // 10 milliseconds
        usleep(10_000);

        if ($assertTranslated) {
            self::assertTrue(Str::startsWith($data['name'], 'de'));
        } else {
            self::assertFalse(Str::startsWith($data['name'], 'de'));
        }
    }

    /**
     * @param string $daily_limit
     * @param string $weekly_limit
     * @param string $monthly_limit
     * @return array
     */
    protected function makeTestData(
        string $daily_limit,
        string $weekly_limit,
        string $monthly_limit,
    ): array {
        $organization = $this->makeTestOrganization($this->makeIdentity());

        $organization->forceFill([
            'allow_translations' => true,
            'translations_enabled' => true,
            'translations_daily_limit' => $daily_limit,
            'translations_weekly_limit' => $weekly_limit,
            'translations_monthly_limit' => $monthly_limit,
        ])->save();

        $fund = $this->makeTestFund($organization);
        $implementation = $fund->getImplementation();

        $request = BaseFormRequest::create('/', 'GET', [], [], [], [
            'HTTP_Accept' => 'application/json',
            'HTTP_Client-Key' => $implementation->key,
            'HTTP_Client-Type' => 'webshop',
        ]);

        return [
            'request' => $request,
            'fund' => $fund,
        ];
    }
}
