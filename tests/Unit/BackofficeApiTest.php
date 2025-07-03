<?php

namespace Tests\Unit;

use App\Services\BackofficeApiService\BackofficeApi;
use App\Services\Forus\TestData\TestData;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Tests\Traits\MakesTestVouchers;
use Tests\Traits\TestsBackoffice;
use Throwable;

class BackofficeApiTest extends TestCase
{
    use MakesTestFunds;
    use TestsBackoffice;
    use MakesTestVouchers;
    use DatabaseTransactions;
    use MakesTestOrganizations;

    /**
     * Tests the eligibility check functionality by simulating API responses.
     *
     * This method generates test certificates, sets up a fund, and performs
     * eligibility checks for two different IDs. It asserts whether each ID is
     * eligible or not and verifies that the log fields are correctly set.
     *
     * @throws Throwable
     * @return void
     */
    public function testEligibilityCheck(): void
    {
        $credentials = self::generateTestBackofficeCredentials();
        $fund = $this->makeAndSetupBackofficeTestFund('fund_001', $credentials);
        $api = $fund->getBackofficeApi();

        $bsn1 = TestData::randomFakeBsn();
        $bsn2 = TestData::randomFakeBsn();

        $this->setupBackofficeResponses(
            $credentials,
            fundKeys: [$fund->fund_config->key],
            eligibleBsn: [$bsn1],
        );

        $response = $api->eligibilityCheck($bsn1);
        $log = $response->getLog();

        $this->assertTrue($response->isEligible());
        $this->assertBackofficeLogFields($log, $bsn1, BackofficeApi::ACTION_ELIGIBILITY_CHECK, BackofficeApi::STATE_SUCCESS);

        $response = $api->eligibilityCheck($bsn2);
        $log = $response->getLog();

        $this->assertFalse($response->isEligible());
        $this->assertBackofficeLogFields($log, $bsn2, BackofficeApi::ACTION_ELIGIBILITY_CHECK, BackofficeApi::STATE_SUCCESS);
    }

    /**
     * Tests the residency check functionality of the backoffice API.
     *
     * This method generates test certificates, sets up a test fund,
     * and performs residency checks for two different identifiers. It asserts that
     * the first identifier is recognized as a resident, while the second is not.
     * Additionally, it verifies the correctness of the log fields for each check.
     *
     * @throws Throwable
     * @return void
     */
    public function testResidencyCheck(): void
    {
        $credentials = self::generateTestBackofficeCredentials();
        $fund = $this->makeAndSetupBackofficeTestFund('fund_001', $credentials);
        $api = $fund->getBackofficeApi();

        $bsn1 = TestData::randomFakeBsn();
        $bsn2 = TestData::randomFakeBsn();

        $this->setupBackofficeResponses(
            $credentials,
            fundKeys: [$fund->fund_config->key],
            residentBsn: [$bsn1],
        );

        $response = $api->residencyCheck($bsn1);
        $log = $response->getLog();

        $this->assertTrue($response->isResident());
        $this->assertBackofficeLogFields($log, $bsn1, BackofficeApi::ACTION_RESIDENCY_CHECK, BackofficeApi::STATE_SUCCESS);

        $response = $api->residencyCheck($bsn2);
        $log = $response->getLog();

        $this->assertFalse($response->isResident());
        $this->assertBackofficeLogFields($log, $bsn2, BackofficeApi::ACTION_RESIDENCY_CHECK, BackofficeApi::STATE_SUCCESS);
    }

    /**
     * Tests the functionality of retrieving a partner's BSN through the backoffice API.
     *
     * This method generates test certificates, sets up a test fund, and simulates two scenarios:
     * 1. A scenario where the partner is found, expecting a valid BSN to be returned.
     * 2. A scenario where the partner is not found, expecting no BSN to be returned.
     *
     * @throws Throwable
     * @return void
     */
    public function testPartnerBSNRequest(): void
    {
        $credentials = self::generateTestBackofficeCredentials();
        $fund = $this->makeAndSetupBackofficeTestFund('fund_001', $credentials);
        $api = $fund->getBackofficeApi();

        $bsn1 = TestData::randomFakeBsn();
        $bsn2 = TestData::randomFakeBsn();
        $bsn3 = TestData::randomFakeBsn();

        $this->setupBackofficeResponses(
            $credentials,
            fundKeys: [$fund->fund_config->key],
            partnerMappings: [$bsn1 => $bsn2, $bsn2 => $bsn1],
        );

        // Test partner found
        $response = $api->partnerBsn($bsn1);
        $log = $response->getLog();

        $this->assertSame($bsn2, $response->getBsn());
        $this->assertBackofficeLogFields($log, $bsn1, BackofficeApi::ACTION_PARTNER_BSN, BackofficeApi::STATE_SUCCESS);

        // Test partner not found
        $response = $api->partnerBsn($bsn3);
        $log = $response->getLog();

        $this->assertFalse($response->getBsn());
        $this->assertBackofficeLogFields($log, $bsn3, BackofficeApi::ACTION_PARTNER_BSN, BackofficeApi::STATE_SUCCESS);
    }

    /**
     * Tests the received and first use request for a voucher.
     *
     * This method generates test certificates, sets up a fund with a voucher,
     * simulates receiving and first use reports, asserts log fields,
     * and sends logs through an artisan command.
     *
     * @return void
     */
    public function testReceivedAndFirstUseRequest(): void
    {
        $credentials = self::generateTestBackofficeCredentials();
        $fund = $this->makeAndSetupBackofficeTestFund('fund_001', $credentials);
        $voucher = $this->makeTestVoucher($fund, $this->makeIdentity(), amount: 100);
        $bsn = TestData::randomFakeBsn();

        $this->setupBackofficeResponses(
            $credentials,
            fundKeys: [$fund->fund_config->key],
        );

        $voucher->identity->setBsnRecord($bsn);
        $voucher->reportBackofficeReceived();

        $this->assertBackofficeLogFields(
            $voucher->backoffice_log_received()->first(),
            $voucher->identity->bsn,
            BackofficeApi::ACTION_REPORT_RECEIVED,
            BackofficeApi::STATE_PENDING,
        );

        $this->artisan('funds.backoffice:send-logs');

        $this->assertBackofficeLogFields(
            $voucher->backoffice_log_received()->first(),
            $voucher->identity->bsn,
            BackofficeApi::ACTION_REPORT_RECEIVED,
            BackofficeApi::STATE_SUCCESS,
        );

        $voucher->reportBackofficeFirstUse();

        $this->assertBackofficeLogFields(
            $voucher->backoffice_log_first_use()->first(),
            $voucher->identity->bsn,
            BackofficeApi::ACTION_REPORT_FIRST_USE,
            BackofficeApi::STATE_PENDING,
        );

        $this->artisan('funds.backoffice:send-logs');

        $this->assertBackofficeLogFields(
            $voucher->backoffice_log_first_use()->first(),
            $voucher->identity->bsn,
            BackofficeApi::ACTION_REPORT_FIRST_USE,
            BackofficeApi::STATE_SUCCESS,
        );
    }

    /**
     * Tests the retry mechanism for backoffice log sending.
     *
     * This method sets up a test fund and voucher, configures mock responses,
     * simulates log sending, and asserts the correct state and attempt count of
     * the backoffice logs. It also tests different scenarios including error states
     * and maintenance periods to ensure the retry logic behaves as expected.
     */
    public function testSendingLogRetries(): void
    {
        $credentials = self::generateTestBackofficeCredentials();
        $fund = $this->makeAndSetupBackofficeTestFund('fund_001', $credentials);
        $voucher = $this->makeTestVoucher($fund, $this->makeIdentity(), amount: 100);
        $bsn = TestData::randomFakeBsn();

        $this->setupBackofficeResponses(
            $credentials,
            fundKeys: [$fund->fund_config->key],
            showError: true,
        );

        $voucher->identity->setBsnRecord($bsn);
        $voucher->reportBackofficeReceived();

        $this->assertBackofficeLogFields(
            $voucher->backoffice_log_received()->first(),
            $voucher->identity->bsn,
            BackofficeApi::ACTION_REPORT_RECEIVED,
            BackofficeApi::STATE_PENDING,
        );

        $this->artisan('funds.backoffice:send-logs');

        $this->assertSame(1, $voucher->backoffice_log_received()->first()->attempts);

        $this->assertBackofficeLogFields(
            $voucher->backoffice_log_received()->first(),
            $voucher->identity->bsn,
            BackofficeApi::ACTION_REPORT_RECEIVED,
            BackofficeApi::STATE_ERROR,
        );

        $this->travelTo(now()->addHours(7));
        $this->artisan('funds.backoffice:send-logs');
        $this->assertSame(1, $voucher->backoffice_log_received()->first()->attempts);

        $this->assertBackofficeLogFields(
            $voucher->backoffice_log_received()->first(),
            $voucher->identity->bsn,
            BackofficeApi::ACTION_REPORT_RECEIVED,
            BackofficeApi::STATE_ERROR,
        );

        $this->setupBackofficeResponses(
            $credentials,
            fundKeys: [$fund->fund_config->key],
            showMaintenance: true,
        );

        $this->travelTo(now()->addHours(8)->addMinutes(5));
        $this->artisan('funds.backoffice:send-logs');
        $this->assertSame(2, $voucher->backoffice_log_received()->first()->attempts);

        $this->assertBackofficeLogFields(
            $voucher->backoffice_log_received()->first(),
            $voucher->identity->bsn,
            BackofficeApi::ACTION_REPORT_RECEIVED,
            BackofficeApi::STATE_ERROR,
        );

        $this->setupBackofficeResponses(
            $credentials,
            fundKeys: [$fund->fund_config->key],
        );

        $this->travelTo(now()->addHours(16)->addMinutes(10));

        $this->artisan('funds.backoffice:send-logs');
        $this->assertSame(3, $voucher->backoffice_log_received()->first()->attempts);

        $this->assertBackofficeLogFields(
            $voucher->backoffice_log_received()->first(),
            $voucher->identity->bsn,
            BackofficeApi::ACTION_REPORT_RECEIVED,
            BackofficeApi::STATE_SUCCESS,
        );
    }

    /**
     * Tests the eligibility check with an invalid authorization token.
     *
     * This method sets up a test fund with an invalid backoffice key, simulates the API response,
     * and asserts that the response code is 403 and the error message is "Invalid Access Token."
     * It also verifies the correctness of the log fields.
     */
    public function testEligibilityCheckWithInvalidAuthorizationToken(): void
    {
        $credentials = self::generateTestBackofficeCredentials();
        $fund = $this->makeAndSetupBackofficeTestFund('fund_001', [...$credentials, 'backoffice_key' => 'invalid_token']);

        $this->setupBackofficeResponses($credentials);

        $response = $fund->getBackofficeApi()->eligibilityCheck('123456789');
        $responseLog = $response->getLog();

        self::assertSame(403, $responseLog['response_code']);
        self::assertSame('Invalid Access Token.', $responseLog['response_body']['error']);

        $this->assertBackofficeLogFields(
            $responseLog,
            '123456789',
            BackofficeApi::ACTION_ELIGIBILITY_CHECK,
            BackofficeApi::STATE_ERROR,
        );
    }

    /**
     * Tests the eligibility check with an invalid fund key.
     *
     * This method generates test certificates, sets up a test fund,
     * configures responses, and checks if the backoffice API correctly
     * returns a 422 response code and an error message indicating an
     * invalid fund key.
     */
    public function testInvalidFundKey(): void
    {
        $credentials = self::generateTestBackofficeCredentials();
        $fund = $this->makeAndSetupBackofficeTestFund('fund_999', $credentials);

        $this->setupBackofficeResponses($credentials);

        $response = $fund->getBackofficeApi()->eligibilityCheck('123456789');
        $responseLog = $response->getLog();

        self::assertSame(422, $responseLog['response_code']);
        self::assertSame('Invalid request.', $responseLog['response_body']['message']);
        self::assertSame('Invalid fund key.', $responseLog['response_body']['errors']['fund_key']);
    }

    /**
     * Tests the eligibility check with an invalid BSN format.
     *
     * This test ensures that when a BSN that is not exactly 9 characters long is provided,
     * the backoffice API returns a 422 Unprocessable Entity status code along with an appropriate error message.
     */
    public function testInvalidBsnFormat(): void
    {
        $credentials = self::generateTestBackofficeCredentials();
        $fund = $this->makeAndSetupBackofficeTestFund('fund_001', $credentials);

        $this->setupBackofficeResponses($credentials);

        $response = $fund->getBackofficeApi()->eligibilityCheck('1234567890');
        $responseLog = $response->getLog();

        self::assertSame(422, $responseLog['response_code']);
        self::assertSame('Invalid request.', $responseLog['response_body']['message']);
        self::assertSame('BSN is required and has to be 9 characters long.', $responseLog['response_body']['errors']['bsn']);
    }

    /**
     * @return void
     */
    public function testErrorResponse(): void
    {
        $credentials = self::generateTestBackofficeCredentials();
        $fund = $this->makeAndSetupBackofficeTestFund('fund_001', $credentials);

        $this->setupBackofficeResponses($credentials, showError: true);

        $response = $fund->getBackofficeApi()->eligibilityCheck('1234567890');
        $responseLog = $response->getLog();

        self::assertSame(500, $responseLog['response_code']);
        self::assertSame('Something went wrong.', $responseLog['response_body']['message']);
    }

    /**
     * Tests the response of the backoffice API during maintenance mode.
     *
     * This method generates test certificates, sets up a fund, configures responses to simulate
     * maintenance mode, and checks if the eligibility check returns the expected 503 status code
     * with a 'Maintenance mode.' message.
     */
    public function testMaintenanceResponse(): void
    {
        $credentials = self::generateTestBackofficeCredentials();
        $fund = $this->makeAndSetupBackofficeTestFund('fund_001', $credentials);

        $this->setupBackofficeResponses($credentials, showMaintenance: true);

        $response = $fund->getBackofficeApi()->eligibilityCheck('1234567890');
        $responseLog = $response->getLog();

        self::assertSame(503, $responseLog['response_code']);
        self::assertSame('Maintenance mode.', $responseLog['response_body']['message']);
    }
}
