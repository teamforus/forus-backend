<?php

namespace Browser;

use App\Helpers\Arr;
use App\Models\Employee;
use App\Models\Fund;
use App\Models\FundCriteriaStep;
use App\Models\FundCriterion;
use App\Models\FundRequest;
use App\Models\Identity;
use App\Models\Implementation;
use App\Models\Organization;
use App\Services\MailDatabaseLoggerService\Traits\AssertsSentEmails;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\HasFrontendActions;
use Tests\DuskTestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\Traits\MakesTestFundRequests;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;

class FundActionsTest extends DuskTestCase
{
    use WithFaker;
    use MakesTestFunds;
    use AssertsSentEmails;
    use HasFrontendActions;
    use MakesTestFundRequests;
    use MakesTestOrganizations;

    /**
     * @var string[]
     */
    protected $defaultHeaders = [
        'Accept' => 'application/json',
        'Client-Type' => 'webshop',
    ];

    /**
     * @return void
     * @throws \Throwable
     */
    public function testWebshopFundRequestPayoutFund(): void
    {
        // Select implementation
        $implementation = Implementation::byKey('nijmegen');

        // Models exist
        $this->assertNotNull($implementation);
        $this->assertNotNull($implementation->organization);

        $requester = $this->makeIdentity($this->makeUniqueEmail());
        $fundSettings = $this->getPayoutFundSettings();
        $fund = $this->createFund($implementation->organization, $fundSettings);

        $this->browse(function (Browser $browser) use (
            $implementation, $fund, $requester, $fundSettings
        ) {
            $browser->visit($implementation->urlWebshop());

            $this->loginIdentity($browser, $requester);
            $browser->waitFor('@headerTitle');

            $browser->visit($implementation->urlWebshop("fondsen/$fund->id"));
            $browser->waitFor('@fundTitle');
            $browser->assertSeeIn('@fundTitle', $fund->name);

            // assert request button available
            $browser->waitFor('@requestButton');

            $fundRequest = $this->setCriteriaAndMakeFundRequest(
                $requester, $fund, $fundSettings['requester_records']
            );

            $browser->refresh();

            // assert fund request created and pending request link available on fund page
            $browser->waitFor('@pendingButton');

            // assert fund request created and pending request link available on funds page
            $browser->visit($implementation->urlWebshop("fondsen"));
            $browser->waitFor('@fundsSearchForm');

            // type the fund name in search form
            $browser->within('@fundsSearchForm', function(Browser $browser) use ($fund) {
                $browser->type('@fundsSearchInput', $fund->name);
            });

            // assert pending request link available on funds page list
            $browser->waitFor("@fundItem$fund->id");
            $browser->within("@fundItem$fund->id", function (Browser $browser) {
                $browser->waitFor("@pendingButton");
            });

            // assert pending fund request block visible on fund request page
            $browser->visit($implementation->urlWebshop("fondsen/$fund->id/activeer"));
            $browser->waitFor('@existsFundRequest');

            $this->approveFundRequest($fundRequest);

            // assert payouts link available on fund page
            $browser->visit($implementation->urlWebshop("fondsen/$fund->id"));
            $browser->waitFor('@payoutsButton');

            // assert approved fund request block visible on fund request page
            $browser->visit($implementation->urlWebshop("fondsen/$fund->id/activeer"));
            $browser->waitFor('@approvedFundRequest');

            // create same fund and assert that activate button available as requester
            // has valid records for it from previous fund request
            $fundSettings = $this->getPayoutFundSettings();
            $fund = $this->createFund($implementation->organization, $fundSettings);

            $browser->visit($implementation->urlWebshop("fondsen/$fund->id"));
            $browser->waitFor('@fundTitle');
            $browser->assertSeeIn('@fundTitle', $fund->name);

            // assert activate button available
            $browser->waitFor('@activateButton');

            // assert fund request created and pending request link available on funds page
            $browser->visit($implementation->urlWebshop("fondsen"));

            $browser->waitFor('@fundsSearchForm');

            // type the fund name in search form
            $browser->within('@fundsSearchForm', function(Browser $browser) use ($fund) {
                $browser->type('@fundsSearchInput', $fund->name);
            });

            $browser->waitFor("@fundItem$fund->id");
            $browser->within("@fundItem$fund->id", function (Browser $browser) {
                $browser->waitFor("@activateButton");
            });

            // Logout user
            $this->logout($browser);
        });

        $fund->update(['state' => Fund::STATE_CLOSED]);
    }

    /**
     * @return void
     * @throws \Throwable
     */
    public function testWebshopFundRequestVoucherFund(): void
    {
        // Select implementation
        $implementation = Implementation::byKey('nijmegen');

        // Models exist
        $this->assertNotNull($implementation);
        $this->assertNotNull($implementation->organization);

        $requester = $this->makeIdentity($this->makeUniqueEmail());
        $fundSettings = $this->getVoucherFundSettings();
        $fund = $this->createFund($implementation->organization, $fundSettings);

        $this->browse(function (Browser $browser) use (
            $implementation, $fund, $requester, $fundSettings
        ) {
            $browser->visit($implementation->urlWebshop());

            $this->loginIdentity($browser, $requester);
            $browser->waitFor('@headerTitle');

            // visit fund page and assert request button available
            $browser->visit($implementation->urlWebshop("fondsen/$fund->id"));
            $browser->waitFor('@fundTitle');
            $browser->assertSeeIn('@fundTitle', $fund->name);

            // assert request button available
            $browser->waitFor('@requestButton');

            $fundRequest = $this->setCriteriaAndMakeFundRequest(
                $requester, $fund, $fundSettings['requester_records']
            );

            $browser->refresh();

            // assert fund request created and pending request link available on fund page
            $browser->waitFor('@pendingButton');

            // assert fund request created and pending request link available on funds page
            $browser->visit($implementation->urlWebshop("fondsen"));
            $browser->waitFor('@fundsSearchForm');

            // type the fund name in search form
            $browser->within('@fundsSearchForm', function(Browser $browser) use ($fund) {
                $browser->type('@fundsSearchInput', $fund->name);
            });

            // assert pending request link available on funds page list
            $browser->waitFor("@fundItem$fund->id");
            $browser->within("@fundItem$fund->id", function (Browser $browser) {
                $browser->waitFor("@pendingButton");
            });

            // assert pending fund request block visible on fund activate page
            $browser->visit($implementation->urlWebshop("fondsen/$fund->id/activeer"));
            $browser->waitFor('@existsFundRequest');

            $this->approveFundRequest($fundRequest);

            // assert payouts link available on fund page
            $browser->visit($implementation->urlWebshop("fondsen/$fund->id"));
            $browser->waitFor('@voucherButton');

            // assert approved fund request block visible on fund activate page
            $browser->visit($implementation->urlWebshop("fondsen/$fund->id/activeer"));
            $browser->waitFor('@approvedFundRequest');

            // create same fund and assert that activate button available as requester
            // has valid records for it from previous fund request
            $fundSettings = $this->getVoucherFundSettings();
            $fund = $this->createFund($implementation->organization, $fundSettings);

            $browser->visit($implementation->urlWebshop("fondsen/$fund->id"));
            $browser->waitFor('@fundTitle');
            $browser->assertSeeIn('@fundTitle', $fund->name);

            // assert activate button available
            $browser->waitFor('@activateButton');

            // assert fund request created and pending request link available on funds page
            $browser->visit($implementation->urlWebshop("fondsen"));

            $browser->waitFor('@fundsSearchForm');

            // type the fund name in search form
            $browser->within('@fundsSearchForm', function(Browser $browser) use ($fund) {
                $browser->type('@fundsSearchInput', $fund->name);
            });

            $browser->waitFor("@fundItem$fund->id");
            $browser->within("@fundItem$fund->id", function (Browser $browser) {
                $browser->waitFor("@activateButton");
            });

            // Logout user
            $this->logout($browser);
        });

        $fund->update(['state' => Fund::STATE_CLOSED]);
    }

    /**
     * @param Organization $organization
     * @param array $settings
     * @return Fund
     */
    protected function createFund(Organization $organization, array $settings): Fund
    {
        $fund = $this->makeTestFund($organization, $settings['fund'], $settings['fund_config']);

        $fund->syncAmountPresets($settings['fund_amount_presets']);

        foreach ($settings['fund_criteria'] as $criterion) {
            $stepTitle = Arr::get($criterion, 'step', Arr::get($criterion, 'step.title'));
            $stepFields = is_array(Arr::get($criterion, 'step')) ? Arr::get($criterion, 'step') : [];

            /** @var FundCriteriaStep $stepModel */
            $stepModel = $stepTitle ?
                ($fund->criteria_steps()->firstWhere([
                    'title' => $stepTitle,
                    ...$stepFields,
                ]) ?: $fund->criteria_steps()->forceCreate([
                    'title' => $stepTitle,
                    ...$stepFields,
                ])) : null;

            /** @var FundCriterion $criterionModel */
            $criterionModel = $fund->criteria()->create([
                ...array_except($criterion, ['rules', 'step']),
                'fund_criteria_step_id' => $stepModel?->id,
            ]);

            foreach ($criterion['rules'] ?? [] as $rule) {
                $criterionModel->fund_criterion_rules()->forceCreate($rule);
            }
        }

        $fundFormula = [[
            'type' => 'fixed',
            'amount' => $fund->isTypeBudget() ? 600 : 0,
            'fund_id' => $fund->id,
        ]];

        $fund->fund_formulas()->createMany($fundFormula);

        $organization->forceFill([
            'fund_request_resolve_policy' => Organization::FUND_REQUEST_POLICY_AUTO_REQUESTED,
        ])->save();

        return $fund->refresh();
    }

    /**
     * @param Identity $requester
     * @param Fund $fund
     * @param array $records
     * @return FundRequest
     */
    protected function setCriteriaAndMakeFundRequest(Identity $requester, Fund $fund, array $records): FundRequest
    {
        $recordsList = collect($records)->map(function (string|int $value, string $key) use ($fund) {
            return $this->makeRequestCriterionValue($fund, $key, $value);
        });

        $response = $this->makeFundRequest($requester, $fund, $recordsList, false);
        $response->assertSuccessful();

        /** @var FundRequest $fundRequest */
        $fundRequest = FundRequest::find($response->json('data.id'));
        $this->assertNotNull($fundRequest);

        return $fundRequest;
    }

    /**
     * @param FundRequest $fundRequest
     * @return void
     */
    private function approveFundRequest(FundRequest $fundRequest): void
    {
        /** @var Employee $employee */
        $employee = $fundRequest->fund->organization->employees->first();
        $this->assertNotNull($employee);

        $fundRequest->assignEmployee($employee);
        $fundRequest->refresh();

        $fundRequest->approve();
        $fundRequest->refresh();
    }

    /**
     * @return array
     */
    protected function getPayoutFundSettings(): array
    {
        return [
            'fund' => [
                'type' => 'budget',
                'criteria_editable_after_start' => true,
            ],
            'fund_config' => [
                'outcome_type' => 'payout',
                'auth_2fa_restrict_emails' => true,
                'auth_2fa_restrict_auth_sessions' => true,
                'auth_2fa_restrict_reimbursements' => true,
                'custom_amount_min' => 100,
                'custom_amount_max' => 200,
                'allow_custom_amounts' => true,
                'allow_custom_amounts_validator' => true,
                'allow_preset_amounts' => true,
                'allow_preset_amounts_validator' => true,
                'iban_record_key' => 'iban',
                'iban_name_record_key' => 'iban_name',
            ],
            'fund_amount_presets' => [
                ['name' => 'Preset #1', 'amount' => '10.00'],
                ['name' => 'Preset #2', 'amount' => '20.00'],
                ['name' => 'Preset #3', 'amount' => '30.00'],
            ],
            'fund_criteria' => [[
                'title' => 'Choose your municipality',
                'description' => fake('nl')->text(rand(150, 400)),
                'record_type_key' => 'municipality',
                'operator' => '=',
                'value' => '268',
                'show_attachment' => false,
                'step' => [
                    'title' => 'Step #1',
                    'description' => 'The _short_ __description__ of the step.',
                ],
            ], [
                'title' => 'Choose the number of children',
                'description' => fake('nl')->text(rand(150, 400)),
                'record_type_key' => 'children_nth',
                'operator' => '>',
                'value' => 2,
                'show_attachment' => false,
                'step' => 'Step #1',
            ], [
                'record_type_key' => 'gender',
                'operator' => '=',
                'value' => 'Female',
                'show_attachment' => false,
                'step' => 'Step #2',
                'rules' => [[
                    'record_type_key' => 'municipality',
                    'operator' => '=',
                    'value' => '268',
                ]]
            ], [
                'record_type_key' => 'iban',
                'operator' => '*',
                'value' => '',
                'optional' => false,
                'show_attachment' => false,
                'step' => 'Step #3',
            ], [
                'record_type_key' => 'iban_name',
                'operator' => '*',
                'value' => '',
                'optional' => false,
                'show_attachment' => false,
                'step' => 'Step #3',
            ]],
            'requester_records' => [
                'iban' => $this->faker->iban(),
                'gender' => 'Female',
                'iban_name' => $this->faker->firstName(),
                'children_nth' => 3,
                'municipality' => 268,
            ],
        ];
    }

    /**
     * @return array
     */
    protected function getVoucherFundSettings(): array
    {
        return [
            'fund' => [
                'type' => 'budget',
                'criteria_editable_after_start' => true,
            ],
            'fund_config' => [
                'outcome_type' => 'voucher',
                'auth_2fa_restrict_emails' => true,
                'auth_2fa_restrict_auth_sessions' => true,
                'auth_2fa_restrict_reimbursements' => true,
                'custom_amount_min' => 100,
                'custom_amount_max' => 200,
                'allow_custom_amounts' => true,
                'allow_custom_amounts_validator' => true,
                'allow_preset_amounts' => true,
                'allow_preset_amounts_validator' => true,
            ],
            'fund_amount_presets' => [
                ['name' => 'Preset #1', 'amount' => '10.00'],
                ['name' => 'Preset #2', 'amount' => '20.00'],
                ['name' => 'Preset #3', 'amount' => '30.00'],
            ],
            'fund_criteria' => [[
                'title' => 'Choose your municipality',
                'description' => fake('nl')->text(rand(150, 400)),
                'record_type_key' => 'municipality',
                'operator' => '=',
                'value' => '268',
                'show_attachment' => false,
                'step' => [
                    'title' => 'Step #1',
                    'description' => 'The _short_ __description__ of the step.',
                ],
            ], [
                'title' => 'Choose the number of children',
                'description' => fake('nl')->text(rand(150, 400)),
                'record_type_key' => 'children_nth',
                'operator' => '>',
                'value' => 2,
                'show_attachment' => false,
                'step' => 'Step #1',
            ], [
                'record_type_key' => 'gender',
                'operator' => '=',
                'value' => 'Female',
                'show_attachment' => false,
                'step' => 'Step #2',
                'rules' => [[
                    'record_type_key' => 'municipality',
                    'operator' => '=',
                    'value' => '268',
                ]]
            ]],
            'requester_records' => [
                'gender' => 'Female',
                'children_nth' => 3,
                'municipality' => 268,
            ],
        ];
    }
}
