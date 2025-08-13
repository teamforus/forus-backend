<?php

namespace Tests\Browser;

use App\Models\Employee;
use App\Models\Fund;
use App\Models\FundRequest;
use App\Models\FundRequestClarification;
use App\Models\FundRequestRecord;
use App\Models\Identity;
use App\Models\Implementation;
use App\Models\Organization;
use App\Models\Role;
use Facebook\WebDriver\Exception\ElementClickInterceptedException;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\TimeoutException;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\HasFrontendActions;
use Tests\Browser\Traits\NavigatesFrontendDashboard;
use Tests\Browser\Traits\RollbackModelsTrait;
use Tests\DuskTestCase;
use Tests\Traits\MakesApiRequests;
use Tests\Traits\MakesTestFundRequests;
use Tests\Traits\MakesTestFunds;
use Throwable;

class FundRequestValidatorTest extends DuskTestCase
{
    use WithFaker;
    use MakesTestFunds;
    use MakesApiRequests;
    use HasFrontendActions;
    use RollbackModelsTrait;
    use MakesTestFundRequests;
    use NavigatesFrontendDashboard;

    /**
     * Tests the assignment of a partner BSN to a fund request.
     *
     * @throws Throwable
     * @return void
     */
    public function testFundRequestAssignPartnerBsn(): void
    {
        $fund = $this->setupNewFundAndCriteria();
        $partnerBsn = 123456782;
        $fundRequest = $this->makeIdentityAndFundRequest($fund);

        $this->apiFundRequestAssignRequest(
            $fundRequest,
            $fund->organization->findEmployee($fund->organization->identity),
        )->assertSuccessful();

        $this->rollbackModels([
            [$fund->organization, $fund->organization->only(['bsn_enabled'])],
        ], function () use ($partnerBsn, $fundRequest) {
            $this->browse(function (Browser $browser) use ($partnerBsn, $fundRequest) {
                $this->signInAndOpenFundRequestPage($browser, $fundRequest, $fundRequest->fund->organization->identity);

                $browser
                    ->waitFor('@addPartnerBsnBtn')
                    ->click('@addPartnerBsnBtn');

                $browser
                    ->waitFor('@modalFundRequestRecordCreate')
                    ->within('@modalFundRequestRecordCreate', function (Browser $browser) use ($partnerBsn) {
                        $browser->waitFor('@partnerBsnInput');
                        $browser->type('@partnerBsnInput', $partnerBsn);
                        $browser->click('@verifyBtn');
                        $browser->waitFor('@submitBtn');
                        $browser->click('@submitBtn');
                    });

                $this->assertAndCloseSuccessNotification($browser);

                $record = $fundRequest->records()->where('record_type_key', 'partner_bsn')->first();

                $browser
                    ->waitFor("@tableFundRequestRecordRow$record->id")
                    ->assertSeeIn("@tableFundRequestRecordRow$record->id", $record->record_type->name)
                    ->assertSeeIn("@tableFundRequestRecordRow$record->id", $partnerBsn);

                $this->logout($browser);
            });
        }, function () use ($fundRequest) {
            $fundRequest?->fund && $this->deleteFund($fundRequest?->fund);
        });
    }

    /**
     * Check that fund-request can be accepted, refused or dismissed.
     *
     * @throws Throwable
     */
    public function testFundRequestResolving()
    {
        $fund = $this->setupNewFundAndCriteria();

        $this->rollbackModels([], function () use ($fund) {
            $this->browse(function (Browser $browser) use ($fund) {
                $fundRequest1 = $this->makeIdentityAndFundRequest($fund);

                $this->signInAndOpenFundRequestPage($browser, $fundRequest1, $fundRequest1->fund->organization->identity);

                // assign the employee and approve the fund request
                $this->assignFundRequestAsValidator($browser);
                $this->approveFundRequest($browser);
                $this->assertEquals(FundRequest::STATE_APPROVED, $fundRequest1->fresh()->state);

                // create new fund request, assign employee and assert disregarded
                $fundRequest2 = $this->makeIdentityAndFundRequest($fund);
                $this->goToFundRequestPage($browser, $fundRequest2);
                $this->assignFundRequestAsValidator($browser);
                $this->disregardFundRequest($browser);
                $this->assertEquals(FundRequest::STATE_DISREGARDED, $fundRequest2->fresh()->state);

                // create new fund request, assign employee and assert declined
                $fundRequest3 = $this->makeIdentityAndFundRequest($fund);
                $this->goToFundRequestPage($browser, $fundRequest3);
                $this->assignFundRequestAsValidator($browser);
                $this->declineFundRequest($browser);
                $this->assertEquals(FundRequest::STATE_DECLINED, $fundRequest3->fresh()->state);

                $this->logout($browser);
            });
        }, function () use ($fund) {
            $fund && $this->deleteFund($fund);
        });
    }

    /**
     * Tests the access permissions for employees based on their role and organization affiliation.
     *
     * @throws Throwable
     */
    public function testFundRequestAccessibleByEmployees()
    {
        $fund = $this->setupNewFundAndCriteria();
        $fundRequest = $this->makeIdentityAndFundRequest($fund);

        $this->rollbackModels([], function () use ($fund, $fundRequest) {
            $this->browse(function (Browser $browser) use ($fund, $fundRequest) {
                $roles = Role::pluck('id')->toArray();

                // assert access for organization employee with correct permissions
                $employees1 = $fund->organization->addEmployee($this->makeIdentity($this->makeUniqueEmail()), $roles);
                $this->signInAndOpenFundRequestPage($browser, $fundRequest, $employees1->identity);
                $this->logout($browser);

                // assert no permissions page for organization employee without correct permissions
                $employees2 = $fund->organization->addEmployee($this->makeIdentity($this->makeUniqueEmail()));
                $this->loginIdentity($browser, $employees2->identity);
                $browser->waitFor('@noPermissionsPageContent');
                $this->logout($browser);

                // assert a missing fund request in the list for employee with correct permissions from another organization
                $otherOrganization = $this->makeTestOrganization($this->makeIdentity($this->makeUniqueEmail()));
                $employees3 = $otherOrganization->addEmployee($this->makeIdentity($this->makeUniqueEmail()), $roles);
                $this->loginIdentity($browser, $employees3->identity);
                $this->assertIdentityAuthenticatedOnValidatorDashboard($browser, $employees3->identity);
                $this->goToFundRequestsPage($browser, true);

                $browser->waitFor('@tableFundRequestSearch');
                $browser->typeSlowly('@tableFundRequestSearch', $fundRequest->identity->email, 1);
                $this->assertRowsCount($browser, 0, '@fundRequestsPageContent');
            });
        }, function () use ($fund) {
            $this->deleteFund($fund);
            $fund->organization->employees()->where('created_at', '>=', $this->testStartDateTime)->forceDelete();
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
        $fund = $this->setupNewFundAndCriteria();
        $fundRequest = $this->makeIdentityAndFundRequest($fund);

        $this->rollbackModels([], function () use ($fund, $fundRequest, &$employees) {
            $this->browse(function (Browser $browser) use ($fund, $fundRequest, &$employees) {
                $rolesValidator = Role::where('key', 'validation')->pluck('id')->toArray();
                $rolesSupervisor = Role::where('key', 'supervisor_validator')->pluck('id')->toArray();

                $employeeValidator = $fund->organization->addEmployee($this->makeIdentity($this->makeUniqueEmail()), $rolesValidator);
                $employeeSupervisor = $fund->organization->addEmployee($this->makeIdentity($this->makeUniqueEmail()), $rolesSupervisor);

                $browser->visit($fund->urlValidatorDashboard());

                // Authorize identity and self-assign fund request
                $this->signInAndOpenFundRequestPage($browser, $fundRequest, $employeeValidator->identity);
                $this->assignFundRequestAsValidator($browser);
                $this->resignEmployee($browser);
                $this->logout($browser);

                // Authorize supervisor employee and assign validator employee
                $this->signInAndOpenFundRequestPage($browser, $fundRequest, $employeeSupervisor->identity);
                $this->assignFundRequestAsValidatorAsManager($browser, $employeeValidator);
                $this->resignEmployee($browser);
                $this->logout($browser);
            });
        }, function () use ($fund, $employees) {
            $this->deleteFund($fund);
            $fund->organization->employees()->where('created_at', '>=', $this->testStartDateTime)->forceDelete();
        });
    }

    /**
     * Tests the visibility of fund requests based on different state groups.
     *
     * @throws Throwable
     */
    public function testFundRequestFilterByState()
    {
        $fund = $this->setupNewFundAndCriteria();
        $fundRequest = $this->makeIdentityAndFundRequest($fund);
        $organization = $fundRequest->fund->organization;

        $this->rollbackModels([], function () use ($fundRequest, $organization) {
            $this->browse(function (Browser $browser) use ($fundRequest, $organization) {
                $this->signInAndOpenFundRequestsPage($browser, $organization, $organization->identity);
                $browser->typeSlowly('@tableFundRequestSearch', $fundRequest->identity->email, 1);
                $this->assertExistInList($browser, $fundRequest, 'all', true);

                // pending
                $this->assertExistInList($browser, $fundRequest, 'pending', true);
                $this->assertExistInList($browser, $fundRequest, 'assigned', false);
                $this->assertExistInList($browser, $fundRequest, 'resolved', false);

                // assigned
                $this->goToFundRequestPage($browser, $fundRequest);
                $this->assignFundRequestAsValidator($browser);
                $browser->back();
                $browser->waitFor('@tableFundRequestSearch');

                $this->assertExistInList($browser, $fundRequest, 'pending', false);
                $this->assertExistInList($browser, $fundRequest, 'assigned', true);
                $this->assertExistInList($browser, $fundRequest, 'resolved', false);

                // resolved
                $this->goToFundRequestPage($browser, $fundRequest);
                $this->approveFundRequest($browser);
                $browser->back();
                $browser->waitFor('@tableFundRequestSearch');

                $this->assertExistInList($browser, $fundRequest, 'pending', false);
                $this->assertExistInList($browser, $fundRequest, 'assigned', false);
                $this->assertExistInList($browser, $fundRequest, 'resolved', true);

                $this->logout($browser);
            });
        }, function () use ($fund, $fundRequest) {
            $this->deleteFund($fund);
        });
    }

    /**
     * Check that employee can create and remove their own notes.
     *
     * @throws Throwable
     */
    public function testFundRequestEmployeeNote()
    {
        $fund = $this->setupNewFundAndCriteria();
        $fundRequest = $this->makeIdentityAndFundRequest($fund);

        $this->rollbackModels([], function () use ($fundRequest) {
            $this->browse(function (Browser $browser) use ($fundRequest) {
                $this->signInAndOpenFundRequestPage($browser, $fundRequest, $fundRequest->fund->organization->identity);
                $this->assignFundRequestAsValidator($browser);

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
     *
     * @throws Throwable
     */
    public function testFundRequestRecordEdit()
    {
        $fund = $this->setupNewFundAndCriteria();
        $fundRequest = $this->makeIdentityAndFundRequest($fund);

        $this->rollbackModels([
            [$fund->organization, $fund->organization->only('allow_fund_request_record_edit')],
        ], function () use ($fundRequest) {
            $fundRequest->fund->organization
                ->forceFill(['allow_fund_request_record_edit' => true])
                ->save();

            $this->browse(function (Browser $browser) use ($fundRequest) {
                $this->signInAndOpenFundRequestPage($browser, $fundRequest, $fundRequest->fund->organization->identity);
                $this->assignFundRequestAsValidator($browser);
                $this->assertUpdateFundRequestRecord($browser, $fundRequest);
                $this->logout($browser);
            });
        }, function () use ($fund) {
            $this->deleteFund($fund);
        });
    }

    /**
     * Check that files/history/clarification are available.
     * @throws Throwable
     */
    public function testFundRequestRecordTabs()
    {
        $fund = $this->setupNewFundAndCriteria(true);
        $fundRequest = $this->makeIdentityAndFundRequest($fund, 2);

        $this->rollbackModels([
            [$fund->organization, $fund->organization->only('allow_fund_request_record_edit')],
        ], function () use ($fundRequest) {
            $fundRequest->fund->organization
                ->forceFill(['allow_fund_request_record_edit' => true])
                ->save();

            $this->browse(function (Browser $browser) use ($fundRequest) {
                $this->signInAndOpenFundRequestPage($browser, $fundRequest, $fundRequest->fund->organization->identity);
                $this->assignFundRequestAsValidator($browser);

                $fundRequest->refresh();

                $clarification = $this->requestClarification($fundRequest);
                $this->answerOnFundRequestClarification($fundRequest, $clarification);

                $clarification->refresh();

                // make record update action so the tab with history will be visible too
                $record = $this->assertUpdateFundRequestRecord($browser, $fundRequest);

                // assert files tab
                $browser->waitFor("@fundRequestRecordTabs$record->id @fundRequestRecordFilesTab");
                $browser->click("@fundRequestRecordTabs$record->id @fundRequestRecordFilesTab");

                $browser->within(
                    "@fundRequestRecordTabs$record->id",
                    function (Browser $browser) use ($record) {
                        $files = $record->files()->get()->pluck('original_name')->toArray();
                        $browser->waitFor('@attachmentsTabContent');
                        array_map(fn ($file) => $browser->assertSee($file), $files);
                    }
                );

                // assert clarification tab
                $browser->waitFor("@fundRequestRecordTabs$record->id @fundRequestRecordClarificationsTab");
                $browser->click("@fundRequestRecordTabs$record->id @fundRequestRecordClarificationsTab");

                $browser->within(
                    "@fundRequestRecordTabs$clarification->fund_request_record_id",
                    function (Browser $browser) use ($clarification) {
                        $browser->waitFor('@clarificationsTabContent');
                        $browser->assertSee($clarification->question);
                        $browser->assertSee($clarification->answer);
                    }
                );

                // assert history tab
                $browser->waitFor("@fundRequestRecordTabs$record->id @fundRequestRecordHistoryTab");
                $browser->click("@fundRequestRecordTabs$record->id @fundRequestRecordHistoryTab");
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

                $this->logout($browser);
            });
        }, function () use ($fund) {
            $fund && $this->deleteFund($fund);
        });
    }

    /**
     * Check that the acceptance with presets is working and only allows predefined values.
     *
     * @throws Throwable
     * @return void
     */
    public function testFundRequestAmountPresets()
    {
        $fund = $this->setupNewFundAndCriteria();
        $fundRequest = $this->makeIdentityAndFundRequest($fund);

        $fund->updateFundsConfig([
            'allow_preset_amounts_validator' => true,
        ]);

        $fund->amount_presets()->create([
            'name' => 'AMOUNT OPTION 1',
            'amount' => 100,
        ]);

        $this->rollbackModels([], function () use ($fundRequest) {
            $this->browse(function (Browser $browser) use ($fundRequest) {
                $this->signInAndOpenFundRequestPage($browser, $fundRequest, $fundRequest->fund->organization->identity);
                $this->assignFundRequestAsValidator($browser);

                $amountPreset = $fundRequest->fund->amount_presets[0];
                $optionValue = $amountPreset->name . ' ' . currency_format_locale($amountPreset->amount);

                $this->assertVisibleAmountTypesWhenApproveRequest($browser, false);

                $fundRequest->fund->updateFundsConfig([
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
     *
     * @throws Throwable
     * @return void
     */
    public function testFundRequestCustomAmounts()
    {
        $fund = $this->setupNewFundAndCriteria();
        $fundRequest = $this->makeIdentityAndFundRequest($fund);

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

        $this->rollbackModels([], function () use ($fundRequest) {
            $this->browse(function (Browser $browser) use ($fundRequest) {
                $this->signInAndOpenFundRequestPage($browser, $fundRequest, $fundRequest->fund->organization->identity);
                $this->assignFundRequestAsValidator($browser);

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
     * @param bool $validator
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeoutException
     * @return void
     */
    protected function goToFundRequestPage(Browser $browser, FundRequest $fundRequest, bool $validator = true): void
    {
        $this->goToFundRequestsPage($browser, $validator);
        $this->searchTable($browser, '@tableFundRequest', $fundRequest->identity->email, $fundRequest->id);

        $browser->click("@tableFundRequestRow$fundRequest->id");
        $browser->waitFor('@fundRequestPageContent');
    }

    /**
     * TODO: replace with dusk implementation.
     *
     * @param FundRequest $fundRequest
     * @return FundRequestClarification
     */
    protected function requestClarification(
        FundRequest $fundRequest
    ): FundRequestClarification {
        $questionData = [
            'question' => $this->faker()->text(),
            'files_requirement' => 'required',
            'text_requirement' => 'required',
            'fund_request_record_id' => $fundRequest->records[0]->id,
        ];

        $response = $this->apiMakeFundRequestClarificationRequest($fundRequest, $fundRequest->employee, $questionData)
            ->assertSuccessful()
            ->assertJsonPath('data.question', $questionData['question']);

        return FundRequestClarification::find($response->json('data.id'));
    }

    /**
     * TODO: replace with dusk implementation.
     *
     * @param FundRequest $fundRequest
     * @param FundRequestClarification $clarification
     * @return void
     */
    protected function answerOnFundRequestClarification(
        FundRequest $fundRequest,
        FundRequestClarification $clarification
    ): void {
        $answerData = ['answer' => $this->faker()->text()];
        $answerFileData = ['file' => UploadedFile::fake()->image('doc.jpg'), 'type' => 'fund_request_clarification_proof'];

        // upload files for clarification request
        $answerData['files'] = (array) $this->apiUploadFileRequest($fundRequest->identity, $answerFileData)
            ->assertSuccessful()
            ->json('data.uid');

        $this->apiRespondFundRequestClarificationRequest($clarification, $fundRequest->identity, $answerData)
            ->assertSuccessful()
            ->assertJsonPath('data.answer', $answerData['answer'])
            ->assertJsonPath('data.fund_request_record_id', $fundRequest->records[0]->id)
            ->assertJsonPath('data.state', $fundRequest->clarifications[0]::STATE_ANSWERED);
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
        // the value for the current fund request record is 5
        $record = $fundRequest->records()->first();

        $browser->waitFor("@fundRequestRecordMenuBtn$record->id");
        $browser->click("@fundRequestRecordMenuBtn$record->id");

        $browser->waitFor('@fundRequestRecordEditBtn');
        $browser->click('@fundRequestRecordEditBtn');

        $browser->waitFor('@modalFundRequestRecordEdit');

        $browser->within('@modalFundRequestRecordEdit', function (Browser $browser) {
            // assert validation errors because value can be >= 2
            $this->clearField($browser, '@numberInput');
            $browser->type('@numberInput', 1);
            $browser->click('@submitBtn');
            $browser->waitFor('.form-error');
            $browser->assertVisible('.form-error');

            // assert button is disabled because same value as before
            $this->clearField($browser, '@numberInput');
            $browser->type('@numberInput', 5);
            $browser->assertDisabled('@submitBtn');

            $this->clearField($browser, '@numberInput');
            $browser->type('@numberInput', 4);
            $browser->click('@submitBtn');
        });

        $this->assertAndCloseSuccessNotification($browser);
        $this->assertEquals(4, $record->refresh()->value);

        return $record;
    }

    /**
     * @param Browser $browser
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeoutException
     * @return void
     */
    protected function assignFundRequestAsValidator(Browser $browser): void
    {
        $browser->waitFor('@fundRequestAssignBtn');
        $browser->click('@fundRequestAssignBtn');

        $this->assertAndCloseSuccessNotification($browser);
    }

    /**
     * @param Browser $browser
     * @param Employee $employee
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeoutException
     * @return void
     */
    protected function assignFundRequestAsValidatorAsManager(Browser $browser, Employee $employee): void
    {
        $browser->waitFor('@fundRequestAssignAsSupervisorBtn');
        $browser->click('@fundRequestAssignAsSupervisorBtn');

        $browser->waitFor('@modalAssignValidator');
        $this->changeSelectControl($browser, '@employeeSelect', $employee->identity->email);

        $browser->click('@submitBtn');
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

                    $this->changeSelectControl($browser, '@amountOptionsSelect', $option);
                }

                if ($type === 'predefined') {
                    $this->changeSelectControl($browser, '@amountValueOptionsSelect', $value);
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
     * @param bool $requireFiles
     * @return Fund
     */
    protected function setupNewFundAndCriteria(bool $requireFiles = false): Fund
    {
        // create sponsor and requester identities
        $organization = Implementation::byKey('nijmegen')->organization;
        $fund = $this->makeTestFund($organization);

        $fund->criteria()->delete();

        $fund->criteria()->create([
            'value' => 2,
            'operator' => '>=',
            'show_attachment' => $requireFiles,
            'record_type_key' => 'children_nth',
        ]);

        return $fund;
    }

    /**
     * @param Fund $fund
     * @param int $filesPerRecord
     * @return FundRequest
     */
    protected function makeIdentityAndFundRequest(Fund $fund, int $filesPerRecord = 0): FundRequest
    {
        // create sponsor and requester identities
        $identity = $this->makeIdentity(email: $this->makeUniqueEmail(), bsn: 123456789);

        $records = [[
            'fund_criterion_id' => $fund->criteria[0]?->id,
            'value' => 5,
            'files' => array_map(fn () => $this->apiUploadFileRequest($identity, [
                'file' => UploadedFile::fake()->image('doc.jpg'),
                'type' => 'fund_request_record_proof',
            ])->json('data.uid'), $filesPerRecord > 0 ? range(1, $filesPerRecord) : []),
        ]];

        return FundRequest::find($this
            ->apiMakeFundRequestRequest($identity, $fund, ['records' => $records], false)
            ->assertSuccessful()
            ->json('data.id'));
    }

    /**
     * @param Browser $browser
     * @param FundRequest $fundRequest
     * @param string $state
     * @param bool $assertExists
     * @throws TimeoutException
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @return void
     */
    protected function assertExistInList(
        Browser $browser,
        FundRequest $fundRequest,
        string $state,
        bool $assertExists,
    ): void {
        $browser->click("@fundRequestsStateTab_$state");

        if ($assertExists) {
            $browser->waitFor("@tableFundRequestRow$fundRequest->id");
            $browser->assertVisible("@tableFundRequestRow$fundRequest->id");
        } else {
            $browser->waitUntilMissing("@tableFundRequestRow$fundRequest->id");
            $this->assertRowsCount($browser, 0, '@fundRequestsPageContent');
            $browser->assertNotPresent("@tableFundRequestRow$fundRequest->id");
        }
    }

    /**
     * @param Browser $browser
     * @param FundRequest $fundRequest
     * @param Identity $identity
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeoutException
     * @return void
     */
    protected function signInAndOpenFundRequestPage(
        Browser $browser,
        FundRequest $fundRequest,
        Identity $identity,
    ): void {
        $browser->visit($fundRequest->fund->urlValidatorDashboard());

        // Authorize identity
        $this->loginIdentity($browser, $identity);
        $this->assertIdentityAuthenticatedOnValidatorDashboard($browser, $identity);
        $this->selectDashboardOrganization($browser, $fundRequest->fund->organization);

        $this->goToFundRequestPage($browser, $fundRequest);
    }

    /**
     * @param Browser $browser
     * @param Organization $organization
     * @param Identity $identity
     * @throws TimeoutException
     * @return void
     */
    protected function signInAndOpenFundRequestsPage(
        Browser $browser,
        Organization $organization,
        Identity $identity,
    ): void {
        $browser->visit(Implementation::general()->urlValidatorDashboard());

        // Authorize identity
        $this->loginIdentity($browser, $identity);
        $this->assertIdentityAuthenticatedOnValidatorDashboard($browser, $identity);
        $this->selectDashboardOrganization($browser, $organization);

        $this->goToFundRequestsPage($browser, true);
    }
}
