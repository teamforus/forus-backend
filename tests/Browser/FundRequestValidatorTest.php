<?php

namespace Browser;

use App\Models\Employee;
use App\Models\Fund;
use App\Models\FundRequest;
use App\Models\FundRequestClarification;
use App\Models\FundRequestRecord;
use App\Models\Implementation;
use App\Models\Organization;
use App\Models\Role;
use Facebook\WebDriver\Exception\ElementClickInterceptedException;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\TimeoutException;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Testing\TestResponse;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\HasFrontendActions;
use Tests\Browser\Traits\RollbackModelsTrait;
use Tests\DuskTestCase;
use Tests\Traits\MakesTestFundRequests;
use Tests\Traits\MakesTestFunds;
use Throwable;

class FundRequestValidatorTest extends DuskTestCase
{
    use WithFaker;
    use MakesTestFunds;
    use HasFrontendActions;
    use RollbackModelsTrait;
    use MakesTestFundRequests;

    /**
     * Check that partner bsn can be assigned.
     * @throws Throwable
     */
    public function testFundRequestAssignPartnerBsn()
    {
        $implementation = Implementation::byKey('nijmegen');
        $organization = $implementation->organization;
        $fundRequest = $this->prepareFundRequest($implementation);
        $fundRequest->assignEmployee($organization->findEmployee($organization->identity));
        $fund = $fundRequest->fund;

        $this->rollbackModels([
            [$organization, $organization->only(['bsn_enabled'])],
        ], function () use ($implementation, $organization, $fundRequest) {
            $this->browse(function (Browser $browser) use ($implementation, $organization, $fundRequest) {
                $bsn = 123456781;
                $browser->visit($implementation->urlValidatorDashboard());

                // Authorize identity
                $this->loginIdentity($browser, $organization->identity);
                $this->assertIdentityAuthenticatedOnValidatorDashboard($browser, $organization->identity);
                $this->selectDashboardOrganization($browser, $organization);

                $this->goToFundRequestPage($browser, $fundRequest);

                $browser->waitFor('@addPartnerBsnBtn');
                $browser->click('@addPartnerBsnBtn');

                $browser->waitFor('@modalFundRequestRecordCreate');
                $browser->within('@modalFundRequestRecordCreate', function (Browser $browser) use ($bsn) {
                    $browser->waitFor('@partnerBsnInput');
                    $browser->typeSlowly('@partnerBsnInput', $bsn, 1);
                    $browser->click('@verifyBtn');

                    $browser->waitFor('@submitBtn');
                    $browser->click('@submitBtn');
                });

                $this->assertAndCloseSuccessNotification($browser);

                $record = $fundRequest->records()->where('record_type_key', 'partner_bsn')->first();
                $this->assertNotNull($record);
                $this->assertEquals($bsn, $record->value);

                $browser->waitFor("@tableFundRequestRecordRow$record->id");
                $browser->with("@tableFundRequestRecordRow$record->id", function (Browser $browser) use ($record) {
                    $browser->assertSee($record->record_type->name);
                    $browser->assertSee($record->value);
                });

                $this->logout($browser);
            });
        }, function () use ($fund) {
            $fund && $this->deleteFund($fund);
        });
    }

    /**
     * Check that fund-request can be assigned, accepted, refused or dismissed.
     * @throws Throwable
     */
    public function testFundRequestStateActions()
    {
        $implementation = Implementation::byKey('nijmegen');
        $organization = $implementation->organization;
        $fund = $this->makeTestFund($implementation->organization);
        $fund->criteria()->delete();

        $fund->criteria()->create([
            'value' => 2,
            'operator' => '>=',
            'show_attachment' => false,
            'record_type_key' => 'children_nth',
        ]);

        $this->rollbackModels([], function () use ($implementation, $organization, $fund) {
            $this->browse(function (Browser $browser) use ($implementation, $organization, $fund) {
                $browser->visit($implementation->urlValidatorDashboard());

                // Authorize identity
                $this->loginIdentity($browser, $organization->identity);
                $this->assertIdentityAuthenticatedOnValidatorDashboard($browser, $organization->identity);
                $this->selectDashboardOrganization($browser, $organization);

                // create new fund request, assign employee and assert approved
                $fundRequest = $this->prepareFundRequest($implementation, $fund);
                $this->goToFundRequestPage($browser, $fundRequest);
                $this->assignEmployee($browser);
                $this->approveFundRequest($browser);
                $this->assertEquals(FundRequest::STATE_APPROVED, $fundRequest->fresh()->state);

                // create new fund request, assign employee and assert disregarded
                $fundRequest = $this->prepareFundRequest($implementation, $fund);
                $this->goToFundRequestPage($browser, $fundRequest);
                $this->assignEmployee($browser);
                $this->disregardFundRequest($browser);
                $this->assertEquals(FundRequest::STATE_DISREGARDED, $fundRequest->fresh()->state);

                // create new fund request, assign employee and assert declined
                $fundRequest = $this->prepareFundRequest($implementation, $fund);
                $this->goToFundRequestPage($browser, $fundRequest);
                $this->assignEmployee($browser);
                $this->declineFundRequest($browser);
                $this->assertEquals(FundRequest::STATE_DECLINED, $fundRequest->fresh()->state);

                $this->logout($browser);
            });
        }, function () use ($fund) {
            $fund && $this->deleteFund($fund);
        });
    }

    /**
     * @throws Throwable
     */
    public function testFundRequestAccessible()
    {
        $implementation = Implementation::byKey('nijmegen');
        $organization = $implementation->organization;
        $fundRequest = $this->prepareFundRequest($implementation);
        $fund = $fundRequest->fund;
        $now = now();

        $this->rollbackModels([], function () use ($implementation, $organization, $fundRequest) {
            $this->browse(function (Browser $browser) use ($implementation, $organization, $fundRequest) {
                $browser->visit($implementation->urlValidatorDashboard());

                // assert access for organization employee with correct permissions
                $employee = $organization->addEmployee(
                    $this->makeIdentity($this->makeUniqueEmail()),
                    Role::pluck('id')->toArray(),
                );

                // Authorize identity
                $this->loginIdentity($browser, $employee->identity);
                $this->assertIdentityAuthenticatedOnValidatorDashboard($browser, $employee->identity);
                $this->selectDashboardOrganization($browser, $organization);

                $this->goToFundRequestPage($browser, $fundRequest);

                $this->logout($browser);

                // assert no permissions page for organization employee without correct permissions
                $employee = $organization->addEmployee($this->makeIdentity($this->makeUniqueEmail()));
                $this->loginIdentity($browser, $employee->identity);
                $browser->waitFor('@noPermissionsPageContent');
                $this->logout($browser);

                // assert a missing fund request in the list for employee with correct permissions from another organization
                $otherOrganization = $this->makeTestOrganization($this->makeIdentity($this->makeUniqueEmail()));

                $employee = $otherOrganization->addEmployee(
                    $this->makeIdentity($this->makeUniqueEmail()),
                    Role::pluck('id')->toArray(),
                );

                $this->loginIdentity($browser, $employee->identity);
                $this->assertIdentityAuthenticatedOnValidatorDashboard($browser, $employee->identity);

                $this->goToFundRequestsPage($browser);

                $browser->waitFor('@tableFundRequestSearch');
                $browser->typeSlowly('@tableFundRequestSearch', $fundRequest->identity->email, 1);
                $this->assertRowsCount($browser, 0, '@fundRequestsPageContent');
            });
        }, function () use ($fund, $organization, $now) {
            $fund && $this->deleteFund($fund);
            $organization->employees()->where('created_at', '>=', $now)->forceDelete();
        });
    }

    /**
     * Check that the employee can assign (when not already assigned) and resign (when already assigned) himself.
     * Check that employee managers can assign (when not already assigned) and
     * resign (when already assigned) other employees with the correct permissions.
     * @throws Throwable
     * @return void
     */
    public function testFundRequestAssignEmployee()
    {
        $implementation = Implementation::byKey('nijmegen');
        $organization = $implementation->organization;
        $fundRequest = $this->prepareFundRequest($implementation);
        $fund = $fundRequest->fund;
        $now = now();

        $this->rollbackModels([], function () use ($implementation, $organization, $fundRequest) {
            $this->browse(function (Browser $browser) use ($implementation, $organization, $fundRequest) {
                $supervisorEmployee = $organization->addEmployee(
                    $this->makeIdentity($this->makeUniqueEmail()),
                    Role::where('key', 'supervisor_validator')->pluck('id')->toArray(),
                );

                // create employee with correct permissions
                $employee = $organization->addEmployee(
                    $this->makeIdentity($this->makeUniqueEmail()),
                    Role::where('key', 'validation')->pluck('id')->toArray(),
                );

                $browser->visit($implementation->urlValidatorDashboard());

                // Authorize identity
                $this->loginIdentity($browser, $employee->identity);
                $this->assertIdentityAuthenticatedOnValidatorDashboard($browser, $employee->identity);
                $this->selectDashboardOrganization($browser, $organization);

                $this->goToFundRequestPage($browser, $fundRequest);

                $this->assignEmployee($browser);
                $this->resignEmployee($browser);

                $this->logout($browser);

                $browser->visit($implementation->urlValidatorDashboard());

                // Authorize supervisor employee
                $this->loginIdentity($browser, $supervisorEmployee->identity);
                $this->assertIdentityAuthenticatedOnValidatorDashboard($browser, $supervisorEmployee->identity);
                $this->selectDashboardOrganization($browser, $organization);

                $this->goToFundRequestPage($browser, $fundRequest);

                $this->assignEmployee($browser, $employee);
                $this->resignEmployee($browser);

                $this->logout($browser);
            });
        }, function () use ($fund, $organization, $now) {
            $fund && $this->deleteFund($fund);
            $organization->employees()->where('created_at', '>=', $now)->forceDelete();
        });
    }

    /**
     * Check that requests are shown on the correct tab: state_group param and can be searched by string.
     * @throws Throwable
     */
    public function testFundRequestFilterByState()
    {
        $implementation = Implementation::byKey('nijmegen');
        $organization = $implementation->organization;
        $fundRequest = $this->prepareFundRequest($implementation);
        $fund = $fundRequest->fund;

        $this->rollbackModels([], function () use ($implementation, $organization, $fundRequest) {
            $this->browse(function (Browser $browser) use ($implementation, $organization, $fundRequest) {
                $browser->visit($implementation->urlValidatorDashboard());

                // Authorize identity
                $this->loginIdentity($browser, $organization->identity);
                $this->assertIdentityAuthenticatedOnValidatorDashboard($browser, $organization->identity);
                $this->selectDashboardOrganization($browser, $organization);

                $this->goToFundRequestsPage($browser);

                $browser->typeSlowly('@tableFundRequestSearch', $fundRequest->identity->email, 1);
                $this->assertExistInList($browser, $fundRequest, 'all');

                // pending
                $this->assertExistInList($browser, $fundRequest, 'assigned', false);
                $this->assertExistInList($browser, $fundRequest, 'resolved', false);
                $this->assertExistInList($browser, $fundRequest, 'pending');

                // assigned
                $fundRequest->assignEmployee($organization->findEmployee($organization->identity));
                $this->assertExistInList($browser, $fundRequest, 'resolved', false);
                $this->assertExistInList($browser, $fundRequest, 'pending', false);
                $this->assertExistInList($browser, $fundRequest, 'assigned');

                // resolved
                $this->approveFundRequestApi($organization, $fundRequest);
                $this->assertExistInList($browser, $fundRequest, 'pending', false);
                $this->assertExistInList($browser, $fundRequest, 'assigned', false);
                $this->assertExistInList($browser, $fundRequest, 'resolved');

                $this->logout($browser);
            });
        }, function () use ($fund) {
            $fund && $this->deleteFund($fund);
        });
    }

    /**
     * Check that employee can create and remove their own notes.
     * @throws Throwable
     */
    public function testFundRequestEmployeeNote()
    {
        $implementation = Implementation::byKey('nijmegen');
        $organization = $implementation->organization;
        $fundRequest = $this->prepareFundRequest($implementation);
        $fundRequest->assignEmployee($organization->findEmployee($organization->identity));
        $fund = $fundRequest->fund;

        $this->rollbackModels([], function () use ($implementation, $organization, $fundRequest) {
            $this->browse(function (Browser $browser) use ($implementation, $organization, $fundRequest) {
                $browser->visit($implementation->urlValidatorDashboard());

                // Authorize identity
                $this->loginIdentity($browser, $organization->identity);
                $this->assertIdentityAuthenticatedOnValidatorDashboard($browser, $organization->identity);
                $this->selectDashboardOrganization($browser, $organization);

                $this->goToFundRequestPage($browser, $fundRequest);

                // add note
                $browser->waitFor('@addNoteBtn');
                $browser->click('@addNoteBtn');
                $browser->waitFor('@modalAddNote');

                $noteDescription = $this->faker->text();
                $browser->within('@modalAddNote', function (Browser $browser) use ($noteDescription) {
                    $browser->type('@noteInput', $noteDescription);
                    $browser->click('@submitBtn');
                });

                $this->assertAndCloseSuccessNotification($browser);

                // assert note created
                $notes = $fundRequest->notes()->get();
                $this->assertCount(1, $notes);
                $note = $notes[0];
                $this->assertEquals($noteDescription, $note->description);

                // assert see note in table
                $browser->waitFor("@noteRow$note->id");
                $browser->assertSeeIn("@noteRow$note->id", $noteDescription);

                // delete note
                $browser->waitFor("@noteMenuBtn$note->id");
                $browser->click("@noteMenuBtn$note->id");
                $browser->waitFor('@deleteNoteBtn');
                $browser->click('@deleteNoteBtn');

                // approve note delete
                $browser->waitFor('@modalDangerZone');
                $browser->waitFor('@btnDangerZoneSubmit');
                $browser->press('@btnDangerZoneSubmit');
                $browser->waitUntilMissing('@modalDangerZone');

                $browser->waitUntilMissing("@noteRow$note->id");
                $this->assertCount(0, $fundRequest->notes()->get());

                $this->logout($browser);
            });
        }, function () use ($fund) {
            $fund && $this->deleteFund($fund);
        });
    }

    /**
     * Check that the record can be edited (and it is only possible to change it to a valid value in terms of criteria).
     * @throws Throwable
     */
    public function testFundRequestRecordEdit()
    {
        $implementation = Implementation::byKey('nijmegen');
        $organization = $implementation->organization;

        $fundRequest = $this->prepareFundRequest($implementation);
        $fundRequest->assignEmployee($organization->findEmployee($organization->identity));
        $fund = $fundRequest->fund;

        $this->rollbackModels([
            [$organization, $organization->only('allow_fund_request_record_edit')],
        ], function () use ($implementation, $organization, $fundRequest) {
            $organization->forceFill(['allow_fund_request_record_edit' => true])->save();

            $this->browse(function (Browser $browser) use ($implementation, $organization, $fundRequest) {
                $browser->visit($implementation->urlValidatorDashboard());

                // Authorize identity
                $this->loginIdentity($browser, $organization->identity);
                $this->assertIdentityAuthenticatedOnValidatorDashboard($browser, $organization->identity);
                $this->selectDashboardOrganization($browser, $organization);

                $this->goToFundRequestPage($browser, $fundRequest);
                $this->assertUpdateFundRequestRecord($browser, $fundRequest);

                $this->logout($browser);
            });
        }, function () use ($fund) {
            $fund && $this->deleteFund($fund);
        });
    }

    /**
     * Check that files/history/clarification are available.
     * @throws Throwable
     */
    public function testFundRequestRecordTabs()
    {
        $implementation = Implementation::byKey('nijmegen');
        $organization = $implementation->organization;

        $fundRequest = $this->prepareFundRequest(implementation: $implementation, fileCountPerRecord: 2);
        $fundRequest->assignEmployee($organization->findEmployee($organization->identity));
        $fund = $fundRequest->fund;

        $this->rollbackModels([
            [$organization, $organization->only('allow_fund_request_record_edit')],
        ], function () use ($implementation, $organization, $fundRequest) {
            $organization->forceFill(['allow_fund_request_record_edit' => true])->save();

            $this->browse(function (Browser $browser) use ($implementation, $organization, $fundRequest) {
                $browser->visit($implementation->urlValidatorDashboard());

                // Authorize identity
                $this->loginIdentity($browser, $organization->identity);
                $this->assertIdentityAuthenticatedOnValidatorDashboard($browser, $organization->identity);
                $this->selectDashboardOrganization($browser, $organization);

                $this->goToFundRequestPage($browser, $fundRequest);

                // make clarification so tabs will be visible (as we already have files)
                $clarification = $this->makeClarification($organization, $fundRequest);
                $this->answerOnFundRequestClarification($fundRequest, $clarification);

                // make record update action so the tab with history will be visible too
                $record = $this->assertUpdateFundRequestRecord($browser, $fundRequest);

                // assert files tab
                $browser->waitFor("@fundRequestRecordTabs$record->id @fundRequestRecordFilesTab");
                $browser->click("@fundRequestRecordTabs$record->id @fundRequestRecordFilesTab");
                $this->assertFilesPresent($browser, $record);

                // assert clarification tab
                $browser->waitFor("@fundRequestRecordTabs$record->id @fundRequestRecordClarificationsTab");
                $browser->click("@fundRequestRecordTabs$record->id @fundRequestRecordClarificationsTab");
                $this->assertClarificationPresent($browser, $clarification);

                // assert history tab
                $browser->waitFor("@fundRequestRecordTabs$record->id @fundRequestRecordHistoryTab");
                $browser->click("@fundRequestRecordTabs$record->id @fundRequestRecordHistoryTab");
                $this->assertHistoryLogPresent($browser, $record);

                $this->logout($browser);
            });
        }, function () use ($fund) {
            $fund && $this->deleteFund($fund);
        });
    }

    /**
     * Check that the acceptance with amount presets is working and only allows predefined values.
     * @throws Throwable
     * @return void
     */
    public function testFundRequestAmountPresets()
    {
        $implementation = Implementation::byKey('nijmegen');
        $organization = $implementation->organization;
        $fund = $this->makeTestFund($implementation->organization);
        $fund->criteria()->delete();

        $fund->criteria()->create([
            'value' => 2,
            'operator' => '>=',
            'show_attachment' => false,
            'record_type_key' => 'children_nth',
        ]);

        $fund->updateFundsConfig(['allow_preset_amounts_validator' => true]);

        $fund->amount_presets()->create([
            'name' => 'AMOUNT OPTION 1',
            'amount' => 100,
        ]);

        $this->rollbackModels([], function () use ($implementation, $organization, $fund) {
            $this->browse(function (Browser $browser) use ($implementation, $organization, $fund) {
                $browser->visit($implementation->urlValidatorDashboard());

                // Authorize identity
                $this->loginIdentity($browser, $organization->identity);
                $this->assertIdentityAuthenticatedOnValidatorDashboard($browser, $organization->identity);
                $this->selectDashboardOrganization($browser, $organization);

                // create new fund request, assign employee and assert approved
                $fundRequest = $this->prepareFundRequest($implementation, $fund);
                $this->goToFundRequestPage($browser, $fundRequest);
                $this->assignEmployee($browser);

                $amountPreset = $fund->amount_presets()->first();
                $optionValue = $amountPreset->name . ' ' . currency_format_locale($amountPreset->amount);

                $this->assertVisibleAmountTypesWhenApproveRequest($browser, false);

                $fund->updateFundsConfig([
                    'allow_custom_amounts_validator' => true,
                    'custom_amount_min' => 100,
                    'custom_amount_max' => 200,
                ]);

                $browser->refresh();
                $this->assertVisibleAmountTypesWhenApproveRequest($browser, true);

                $this->approveFundRequestCustomAmount($browser, 'predefined', $optionValue);

                $this->assertEquals(FundRequest::STATE_APPROVED, $fundRequest->fresh()->state);
                $this->assertEquals($amountPreset->amount, $fundRequest->vouchers()->first()->amount);

                $this->logout($browser);
            });
        }, function () use ($fund) {
            $fund && $this->deleteFund($fund);
        });
    }

    /**
     * Check that the acceptance allows custom values within defined ranges.
     * @throws Throwable
     * @return void
     */
    public function testFundRequestCustomAmounts()
    {
        $implementation = Implementation::byKey('nijmegen');
        $organization = $implementation->organization;
        $fund = $this->makeTestFund($implementation->organization);
        $fund->criteria()->delete();

        $fund->criteria()->create([
            'value' => 2,
            'operator' => '>=',
            'show_attachment' => false,
            'record_type_key' => 'children_nth',
        ]);

        $fund->updateFundsConfig(['allow_preset_amounts_validator' => true]);
        $fund->amount_presets()->create([
            'name' => 'AMOUNT OPTION 1',
            'amount' => 100,
        ]);

        $fund->updateFundsConfig([
            'allow_custom_amounts_validator' => true,
            'custom_amount_min' => 100,
            'custom_amount_max' => 200,
        ]);

        $this->rollbackModels([], function () use ($implementation, $organization, $fund) {
            $this->browse(function (Browser $browser) use ($implementation, $organization, $fund) {
                $browser->visit($implementation->urlValidatorDashboard());

                // Authorize identity
                $this->loginIdentity($browser, $organization->identity);
                $this->assertIdentityAuthenticatedOnValidatorDashboard($browser, $organization->identity);
                $this->selectDashboardOrganization($browser, $organization);

                // create new fund request, assign employee and assert approved
                $fundRequest = $this->prepareFundRequest($implementation, $fund);
                $this->goToFundRequestPage($browser, $fundRequest);
                $this->assignEmployee($browser);

                $this->approveFundRequestCustomAmount($browser, 'custom', 150);
                $this->assertEquals(FundRequest::STATE_APPROVED, $fundRequest->fresh()->state);
                $this->assertEquals(150, $fundRequest->vouchers()->first()->amount);

                $this->logout($browser);
            });
        }, function () use ($fund) {
            $fund && $this->deleteFund($fund);
        });
    }

    /**
     * @param Browser $browser
     * @param FundRequest $fundRequest
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeoutException
     * @return void
     */
    protected function goToFundRequestPage(Browser $browser, FundRequest $fundRequest): void
    {
        $this->goToFundRequestsPage($browser);
        $this->searchTable($browser, '@tableFundRequest', $fundRequest->identity->email, $fundRequest->id);

        $browser->click("@tableFundRequestRow$fundRequest->id");
        $browser->waitFor('@fundRequestPageContent');
    }

    /**
     * @param Browser $browser
     * @param FundRequestRecord $record
     * @return void
     */
    protected function assertFilesPresent(Browser $browser, FundRequestRecord $record): void
    {
        $browser->within(
            "@fundRequestRecordTabs$record->id",
            function (Browser $browser) use ($record) {
                $files = $record->files()->get()->pluck('original_name')->toArray();
                $browser->waitFor('@attachmentsTabContent');
                array_map(fn ($file) => $browser->assertSee($file), $files);
            }
        );
    }

    /**
     * @param Browser $browser
     * @param FundRequestClarification $clarification
     * @return void
     */
    protected function assertClarificationPresent(
        Browser $browser,
        FundRequestClarification $clarification,
    ): void {
        $browser->within(
            "@fundRequestRecordTabs$clarification->fund_request_record_id",
            function (Browser $browser) use ($clarification) {
                $browser->waitFor('@clarificationsTabContent');
                $browser->assertSee($clarification->question);
                $browser->assertSee($clarification->answer);
            }
        );
    }

    /**
     * @param Browser $browser
     * @param FundRequestRecord $record
     * @return void
     */
    protected function assertHistoryLogPresent(Browser $browser, FundRequestRecord $record): void
    {
        $browser->within(
            "@fundRequestRecordTabs$record->id",
            function (Browser $browser) use ($record) {
                $log = $record->historyLogs()->first();
                $this->assertNotNull($log);

                $browser->waitFor('@historyTabContent');
                $browser->waitFor("@recordHistoryRow$log->id");
                $browser->assertSeeIn("@recordHistoryRow$log->id", 5);
                $browser->assertSeeIn("@recordHistoryRow$log->id", $record->value);
            }
        );
    }

    /**
     * @param Organization $organization
     * @param FundRequest $fundRequest
     * @return FundRequestClarification
     */
    protected function makeClarification(
        Organization $organization,
        FundRequest $fundRequest
    ): FundRequestClarification {
        $questionToken = $this->requestFundRequestClarification($organization, $fundRequest);
        $clarifications = $fundRequest->clarifications()->get();
        $this->assertCount(1, $clarifications);

        $clarification = $clarifications[0];
        $this->assertEquals($questionToken, $clarification->question);

        return $clarification;
    }

    /**
     * @param Browser $browser
     * @param FundRequest $fundRequest
     * @throws TimeoutException
     * @throws \Facebook\WebDriver\Exception\ElementClickInterceptedException
     * @throws \Facebook\WebDriver\Exception\NoSuchElementException
     * @return FundRequestRecord
     */
    protected function assertUpdateFundRequestRecord(
        Browser $browser,
        FundRequest $fundRequest
    ): FundRequestRecord {
        // the first record is children_nth, and it can be an int and >= 2.
        // value for the current fund request record is 5
        $record = $fundRequest->records()->first();

        $browser->waitFor("@fundRequestRecordMenuBtn$record->id");
        $browser->click("@fundRequestRecordMenuBtn$record->id");

        $browser->waitFor('@fundRequestRecordEditBtn');
        $browser->click('@fundRequestRecordEditBtn');

        $browser->waitFor('@modalFundRequestRecordEdit');

        $browser->within('@modalFundRequestRecordEdit', function (Browser $browser) {
            // assert validation errors because value can be >= 2
            $browser->type('@numberInput', 1);
            $browser->click('@submitBtn');
            $browser->waitFor('.form-error');
            $browser->assertVisible('.form-error');

            // assert button is disabled because same value as before
            $browser->type('@numberInput', 5);
            $browser->assertDisabled('@submitBtn');

            $browser->type('@numberInput', 4);
            $browser->click('@submitBtn');
        });

        $this->assertAndCloseSuccessNotification($browser);
        $this->assertEquals(4, $record->refresh()->value);

        return $record;
    }

    /**
     * @param Organization $organization
     * @param FundRequest $fundRequest
     * @return void
     */
    protected function approveFundRequestApi(
        Organization $organization,
        FundRequest $fundRequest,
    ): void {
        $response = $this->patch(
            "/api/v1/platform/organizations/$organization->id/fund-requests/$fundRequest->id/approve",
            [],
            $this->makeApiHeaders($organization->identity),
        );

        $response->assertSuccessful();
    }

    /**
     * @param Browser $browser
     * @param Employee|null $employee
     * @throws TimeoutException
     * @throws \Facebook\WebDriver\Exception\ElementClickInterceptedException
     * @throws \Facebook\WebDriver\Exception\NoSuchElementException
     * @return void
     */
    protected function assignEmployee(Browser $browser, ?Employee $employee = null): void
    {
        if ($employee) {
            $browser->waitFor('@fundRequestAssignAsSupervisorBtn');
            $browser->click('@fundRequestAssignAsSupervisorBtn');

            $browser->waitFor('@modalAssignValidator');

            $browser->waitFor('@employeeSelect');
            $browser->click('@employeeSelect .select-control-search');
            $this->findOptionElement($browser, '@employeeSelect', $employee->identity->email)->click();

            $browser->click('@submitBtn');
        } else {
            $browser->waitFor('@fundRequestAssignBtn');
            $browser->click('@fundRequestAssignBtn');
        }

        $this->assertAndCloseSuccessNotification($browser);
    }

    /**
     * @param Browser $browser
     * @throws TimeoutException
     * @throws \Facebook\WebDriver\Exception\ElementClickInterceptedException
     * @throws \Facebook\WebDriver\Exception\NoSuchElementException
     * @return void
     */
    protected function resignEmployee(Browser $browser): void
    {
        $browser->waitFor('@fundRequestResignBtn');
        $browser->click('@fundRequestResignBtn');

        $this->assertAndCloseSuccessNotification($browser);
    }

    /**
     * @param Browser $browser
     * @throws TimeoutException
     * @throws \Facebook\WebDriver\Exception\ElementClickInterceptedException
     * @throws \Facebook\WebDriver\Exception\NoSuchElementException
     * @return void
     */
    protected function approveFundRequest(Browser $browser): void
    {
        $browser->waitFor('@fundRequestApproveBtn');
        $browser->click('@fundRequestApproveBtn');

        $browser->waitFor('@modalNotification');
        $browser->within('@modalNotification', fn (Browser $browser) => $browser->click('@submitBtn'));
        $browser->waitUntilMissing('@modalNotification');
    }

    /**
     * @param Browser $browser
     * @param bool $assertVisible
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeoutException
     * @return void
     */
    protected function assertVisibleAmountTypesWhenApproveRequest(Browser $browser, bool $assertVisible): void
    {
        $browser->waitFor('@fundRequestApproveBtn');
        $browser->click('@fundRequestApproveBtn');

        $browser->waitFor('@modalApproveFundRequest');
        $browser->within('@modalApproveFundRequest', function (Browser $browser) use ($assertVisible) {
            $browser->waitFor('@toggleRequestAmountType');
            $browser->click('@toggleRequestAmountType');

            if ($assertVisible) {
                $browser->waitFor('@amountOptionsSelect');
            } else {
                $browser->assertMissing('@amountOptionsSelect');
            }

            $browser->click('@closeBtn');
        });

        $browser->waitUntilMissing('@modalApproveFundRequest');
    }

    /**
     * @param Browser $browser
     * @param string $type
     * @param string|int $value
     * @param bool $skipSelectType
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeoutException
     * @return void
     */
    protected function approveFundRequestCustomAmount(
        Browser $browser,
        string $type,
        string|int $value,
        bool $skipSelectType = false,
    ): void {
        $browser->waitFor('@fundRequestApproveBtn');
        $browser->click('@fundRequestApproveBtn');

        $browser->waitFor('@modalApproveFundRequest');
        $browser->within(
            '@modalApproveFundRequest',
            function (Browser $browser) use ($type, $value, $skipSelectType) {
                $browser->waitFor('@toggleRequestAmountType');
                $browser->click('@toggleRequestAmountType');

                if (!$skipSelectType) {
                    $option = match ($type) {
                        'predefined' => 'Vaste bedragen op basis van categorieën',
                        'custom' => 'Vrij bedrag',
                    };

                    $browser->waitFor('@amountOptionsSelect');
                    $browser->click('@amountOptionsSelect .select-control-search');
                    $this->findOptionElement($browser, '@amountOptionsSelect', $option)->click();
                }

                if ($type === 'predefined') {
                    $browser->waitFor('@amountValueOptionsSelect');
                    $browser->click('@amountValueOptionsSelect .select-control-search');
                    $this->findOptionElement($browser, '@amountValueOptionsSelect', $value)->click();
                } else {
                    $browser->waitFor('@amountCustomInput');
                    $browser->type('@amountCustomInput', $value);
                }

                $browser->click('@submitBtn');
            }
        );

        $browser->waitUntilMissing('@modalApproveFundRequest');
    }

    /**
     * @param Browser $browser
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeoutException
     * @return void
     */
    protected function disregardFundRequest(Browser $browser): void
    {
        $browser->waitFor('@fundRequestDisregardBtn');
        $browser->click('@fundRequestDisregardBtn');

        $browser->waitFor('@modalDisregardFundRequest');
        $browser->within('@modalDisregardFundRequest', fn (Browser $browser) => $browser->click('@submitBtn'));

        $this->assertAndCloseSuccessNotification($browser);
    }

    /**
     * @param Browser $browser
     * @throws TimeoutException
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @return void
     */
    protected function declineFundRequest(Browser $browser): void
    {
        $browser->waitFor('@fundRequestDeclineBtn');
        $browser->click('@fundRequestDeclineBtn');

        $browser->waitFor('@modalDeclineFundRequest');
        $browser->within('@modalDeclineFundRequest', fn (Browser $browser) => $browser->click('@submitBtn'));

        $this->assertAndCloseSuccessNotification($browser);
    }

    /**
     * @param Implementation $implementation
     * @param Fund|null $fund
     * @param int $fileCountPerRecord
     * @return FundRequest
     */
    protected function prepareFundRequest(
        Implementation $implementation,
        ?Fund $fund = null,
        int $fileCountPerRecord = 0
    ): FundRequest {
        $requesterIdentity = $this->makeIdentity(email: $this->makeUniqueEmail(), bsn: 123456789);

        if (!$fund) {
            $fund = $this->makeTestFund($implementation->organization);

            $fund->criteria()->delete();

            $fund->criteria()->create([
                'value' => 2,
                'operator' => '>=',
                'show_attachment' => $fileCountPerRecord > 0,
                'record_type_key' => 'children_nth',
            ]);
        }

        $files = [];

        for ($i = 0; $i < $fileCountPerRecord; $i++) {
            $file = $this->makeRecordProofFile($this->makeApiHeaders($this->makeIdentityProxy($requesterIdentity)));
            $files = [
                ...$files,
                $file->json('data.uid'),
            ];
        }

        $records = [[
            'fund_criterion_id' => $fund->criteria[0]?->id,
            'value' => 5,
            'files' => $files,
        ]];

        $response = $this->makeFundRequest($requesterIdentity, $fund, $records, false);
        $response->assertSuccessful();

        /** @var FundRequest $fundRequest */
        $fundRequest = FundRequest::find($response->json('data.id'));
        $this->assertNotNull($fundRequest);

        return $fundRequest;
    }

    /**
     * @param array $headers
     * @return \Illuminate\Testing\TestResponse
     */
    protected function makeRecordProofFile(array $headers): TestResponse
    {
        $type = 'fund_request_record_proof';
        $filePath = base_path('tests/assets/test.png');
        $file = UploadedFile::fake()->createWithContent($this->faker()->uuid . '.png', $filePath);

        $response = $this->postJson('/api/v1/files', compact('type', 'file'), $headers);
        $response->assertCreated();

        return $response;
    }

    /**
     * @param Browser $browser
     * @param FundRequest $fundRequest
     * @param string $state
     * @param bool $assertExists
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeoutException
     * @return void
     */
    protected function assertExistInList(
        Browser $browser,
        FundRequest $fundRequest,
        string $state,
        bool $assertExists = true,
    ): void {
        $browser->click("@fundRequestsStateTab_$state");

        if ($assertExists) {
            $browser->waitFor("@tableFundRequestRow$fundRequest->id", 20);
            $browser->assertVisible("@tableFundRequestRow$fundRequest->id");
        } else {
            $this->assertRowsCount($browser, 0, '@fundRequestsPageContent');
            $browser->assertMissing("@tableFundRequestRow$fundRequest->id");
        }
    }

    /**
     * @param FundRequest $fundRequest
     * @param FundRequestClarification $clarification
     * @return void
     */
    protected function answerOnFundRequestClarification(
        FundRequest $fundRequest,
        FundRequestClarification $clarification
    ): void {
        $this->patchJson(
            "/api/v1/platform/fund-requests/$fundRequest->id/clarifications/$clarification->id",
            [
                'answer' => 'answer',
                'files' => [],
            ],
            $this->makeApiHeaders($fundRequest->identity)
        )->assertSuccessful();

        $clarification->refresh();
        $this->assertEquals('answer', $clarification->answer);
        $this->assertEquals(FundRequestClarification::STATE_ANSWERED, $clarification->state);
        $this->assertNotNull($clarification->answered_at);
    }
}
