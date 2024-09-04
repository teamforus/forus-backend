<?php

namespace Tests\Feature;

use App\Models\Fund;
use App\Models\FundAmountPreset;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Arr;
use Tests\TestCase;
use Tests\Traits\MakesProductReservations;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;

class PayoutsTest extends TestCase
{
    use MakesTestFunds;
    use DatabaseTransactions;
    use MakesTestOrganizations;
    use MakesProductReservations;

    /**
     * A basic feature test example.
     */
    public function testFundPayoutSettingsWhileNotAllowed(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $organization->forceFill([ 'allow_payouts' => false ])->update();

        $this->configureFundPayoutConfig($fund);
        $this->assertPayoutsNotUpdated($fund);
    }

    /**
     * A basic feature test example.
     */
    public function testFundPayoutSettingsWhileAllowed(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $organization->forceFill([ 'allow_payouts' => true ])->update();

        $this->configureFundPayoutConfig($fund);
        $this->assertPayoutsUpdated($fund);
    }

    /**
     * A basic feature test example.
     */
    public function testFundPayoutSettingsUpdatePresets(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $organization->forceFill([ 'allow_payouts' => true ])->update();

        $this->configureFundPayoutConfig($fund);
        $this->assertPayoutsUpdated($fund);

        $presets = $fund->amount_presets->map(fn (FundAmountPreset $preset) => $preset->only([
            'id', 'amount', 'name',
        ]))->toArray();

        $presets[0]['name'] = 'Updated';
        $presets[0]['amount'] = '50.00';
        $presets[2] = [ 'name' => 'New', 'amount' => '100.00' ];

        $res = $this->patchJson(
            "/api/v1/platform/organizations/$fund->organization_id/funds/$fund->id",
            [ 'amount_presets' => $presets],
            $this->makeApiHeaders($this->makeIdentityProxy($fund->organization->identity)),
        );

        $res->assertSuccessful();
        $fund->refresh();

        self::assertEquals(
            array_sum(array_pluck($presets, 'amount')),
            $fund->amount_presets->sum('amount'),
        );
    }

    /**
     * @param Fund $fund
     * @return void
     */
    protected function configureFundPayoutConfig(Fund $fund): void
    {
        $res = $this->patchJson(
            "/api/v1/platform/organizations/$fund->organization_id/funds/$fund->id",
            $this->getFundPayoutConfigs(),
            $this->makeApiHeaders($this->makeIdentityProxy($fund->organization->identity)),
        );

        $res->assertSuccessful();
        $fund->refresh();
    }

    /**
     * @param Fund $fund
     * @return void
     */
    protected function assertPayoutsNotUpdated(Fund $fund): void {
        self::assertNull($fund->fund_config->custom_amount_min);
        self::assertNull($fund->fund_config->custom_amount_max);
        self::assertFalse($fund->fund_config->allow_preset_amounts);
        self::assertFalse($fund->fund_config->allow_preset_amounts_validator);
        self::assertFalse($fund->fund_config->allow_custom_amounts);
        self::assertFalse($fund->fund_config->allow_custom_amounts_validator);
        self::assertEmpty($fund->amount_presets->toArray());
    }

    /**
     * @param Fund $fund
     * @return void
     */
    protected function assertPayoutsUpdated(Fund $fund): void {
        $configs = $this->getFundPayoutConfigs();

        self::assertEquals($configs['custom_amount_min'], $fund->fund_config->custom_amount_min);
        self::assertEquals($configs['custom_amount_max'], $fund->fund_config->custom_amount_max);
        self::assertEquals($configs['allow_preset_amounts'], $fund->fund_config->allow_preset_amounts);
        self::assertEquals($configs['allow_preset_amounts_validator'], $fund->fund_config->allow_preset_amounts_validator);
        self::assertEquals($configs['allow_custom_amounts'], $fund->fund_config->allow_custom_amounts);
        self::assertEquals($configs['allow_custom_amounts_validator'], $fund->fund_config->allow_custom_amounts_validator);

        self::assertEquals(
            array_sum(Arr::pluck($configs['amount_presets'], 'amount')),
            $fund->amount_presets->sum('amount'),
        );
    }

    /**
     * @return array
     */
    protected function getFundPayoutConfigs(): array
    {
        return [
            'custom_amount_min' => '10.00',
            'custom_amount_max' => '100.00',
            'allow_preset_amounts' => true,
            'allow_preset_amounts_validator' => true,
            'allow_custom_amounts' => true,
            'allow_custom_amounts_validator' => true,
            'amount_presets' => [
                ['name' => 'Preset #1', 'amount' => '10.00'],
                ['name' => 'Preset #2', 'amount' => '20.00'],
                ['name' => 'Preset #3', 'amount' => '30.00'],
            ]
        ];
    }
}
