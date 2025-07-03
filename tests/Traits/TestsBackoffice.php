<?php

namespace Tests\Traits;

use App\Models\Fund;
use App\Models\FundBackofficeLog;
use App\Rules\BsnRule;
use App\Services\BackofficeApiService\BackofficeApi;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use PHPUnit\Framework\Assert;
use ReflectionObject;
use Tests\TestCase;
use Throwable;

/**
 * @mixin TestCase
 */
trait TestsBackoffice
{
    /**
     * Sets up fake HTTP responses for testing purposes.
     *
     * This method configures the HTTP client to respond with predefined data based on various conditions,
     * such as authentication, request headers, and payload validation. It allows simulating different scenarios
     * like errors, maintenance mode, and various actions related to funds handling.
     *
     * @param array $credentials Array containing backoffice credentials including certificate, client cert, and key.
     * @param array $fundKeys Array of valid fund keys that can be used in requests.
     * @param array $residentBsn Array of BSN numbers considered as residents.
     * @param array $eligibleBsn Array of BSN numbers considered eligible for certain actions.
     * @param array $partnerMappings Associative array mapping BSN numbers to partner BSN numbers.
     * @param bool $showError Boolean indicating whether to simulate an error response.
     * @param bool $showMaintenance Boolean indicating whether to simulate a maintenance mode response.
     *
     * @return void
     */
    public static function setupBackofficeResponses(
        array $credentials,
        array $fundKeys = [
            // 'fund_001', 'fund_002', 'fund_003'
        ],
        array $residentBsn = [
            //'123456789', '987654321',
        ],
        array $eligibleBsn = [
            // '123456789'
        ],
        array $partnerMappings = [
            // '987654321' => '123456789',
            // '123456789' => '987654321',
        ],
        bool $showError = false,
        bool $showMaintenance = false,
    ): void {
        self::clearExistingFakes();

        // these should match your .env values
        Http::fake(function (Request $request, array $options) use (
            $credentials,
            $residentBsn,
            $fundKeys,
            $partnerMappings,
            $eligibleBsn,
            $showError,
            $showMaintenance,
        ) {
            $caPath = $options['verify'] ?? null;
            $certPath = $options['cert'] ?? null;
            $keyPath = $options['ssl_key'] ?? null;

            Assert::assertNotNull($caPath, 'Missing CA verify path');
            Assert::assertNotNull($certPath, 'Missing client cert path');
            Assert::assertNotNull($keyPath, 'Missing client key path');

            // 2) Read & compare the actual file contents
            Assert::assertSame($credentials['backoffice_certificate'], file_get_contents($caPath), 'CA certificate mismatch');
            Assert::assertSame($credentials['backoffice_client_cert'], file_get_contents($certPath), 'Client certificate mismatch');
            Assert::assertSame($credentials['backoffice_client_cert_key'], file_get_contents($keyPath), 'Client key mismatch');

            // 1) Simulation errors
            if ($showError) {
                return Http::response(['message' => 'Something went wrong.'], 500);
            }

            if ($showMaintenance) {
                return Http::response(['message' => 'Maintenance mode.'], 503);
            }

            // 2) Auth
            $header = $request->header('Authorization');

            if (!$header) {
                return Http::response(['error' => 'Not Authenticated.'], 403);
            }

            if ($header[0] !== 'Bearer ' . $credentials['backoffice_key']) {
                return Http::response(['error' => 'Invalid Access Token.'], 403);
            }

            // 3) Accept & Content-Type
            $accepts = $request->header('Accept');
            $content = $request->header('Content-Type');

            if (!$accepts || $accepts[0] !== 'application/json') {
                return Http::response(['error' => 'Invalid Accept header.'], 403);
            }

            if (!$content || $content[0] !== 'application/json') {
                return Http::response(['message' => 'Invalid Content-Type header.'], 403);
            }

            // 4) Funds endpoints
            $url = $request->url();
            $method = $request->method();

            if ($method === 'POST' && str_ends_with($url, '/funds')) {
                $body = $request->data();
                $action = $body['action'] ?? null;
                $fund = $body['fund_key'] ?? null;
                $bsn = (string) ($body['bsn'] ?? '');
                $requestId = (string) ($body['id'] ?? '');

                // 4a) Validate payload
                $errors = [];

                if (!in_array($action, ['eligibility_check', 'residency_check', 'partner_bsn', 'received', 'first_use'], true)) {
                    $errors['action'] = 'Invalid action type.';
                }

                if (!in_array($fund, $fundKeys, true)) {
                    $errors['fund_key'] = 'Invalid fund key.';
                }

                if (!(new BsnRule())->passes('', $bsn)) {
                    $errors['bsn'] = 'BSN is required and has to be 9 characters long.';
                }

                if (!empty($errors)) {
                    return Http::response([
                        'message' => 'Invalid request.',
                        'errors' => $errors,
                    ], 422);
                }

                // 4b) Dispatch action
                switch ($action) {
                    case 'received':
                    case 'first_use':
                        return Http::response([
                            'id' => $requestId,
                        ], 201);
                    case 'eligibility_check':
                        return Http::response([
                            'eligible' => in_array($bsn, $eligibleBsn, true),
                            'id' => $requestId,
                        ]);
                    case 'residency_check':
                        return Http::response([
                            'id' => $requestId,
                            'resident' => in_array($bsn, $residentBsn, true),
                        ]);
                    case 'partner_bsn':
                        return Http::response([
                            'id' => $requestId,
                            'partner_bsn' => $partnerMappings[$bsn] ?? null,
                        ]);
                }

                // 4c) Fallback
                return Http::response(['message' => 'Not found.'], 404);
            }

            // 5) Status endpoint
            if ($method === 'GET' && str_ends_with($url, '/status')) {
                return Http::response(['status' => 'ok']);
            }

            // 6) Fallback
            return Http::response(['message' => 'Not found.'], 404);
        });
    }

    /**
     * Creates and configures a test fund with given key and credentials.
     *
     * @param string $fundKey The unique identifier for the fund
     * @param array $credentials Array containing backoffice credentials including keys and certificates
     * @return Fund A fully configured test fund model
     */
    protected function makeAndSetupBackofficeTestFund(string $fundKey, array $credentials): Fund
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $organization->update([
            'bsn_enabled' => true,
            'backoffice_available' => true,
        ]);

        $fund->fund_config->forceFill([
            'key' => $fundKey,
            'backoffice_url' => 'https://server.sponsor-api.com:4000',
            'backoffice_key' => $credentials['backoffice_key'],
            'backoffice_enabled' => true,
            'backoffice_certificate' => $credentials['backoffice_certificate'],
            'backoffice_client_cert' => $credentials['backoffice_client_cert'],
            'backoffice_client_cert_key' => $credentials['backoffice_client_cert_key'],
        ])->save();

        return $fund;
    }

    /**
     * Asserts that log fields match expected values for a backoffice operation.
     *
     * @param FundBackofficeLog $log The log entry to validate
     * @param string $bsn The business service number (BSN) to check against the log
     * @param string $action The action performed, as recorded in the log
     * @param string $state The expected state of the operation (default: STATE_SUCCESS)
     *
     * This method performs several checks:
     * 1. Verifies that the log's state matches the provided state.
     * 2. If the state is SUCCESS, ensures the request ID matches the response body ID.
     * 3. Confirms that the action and BSN in the log match the provided values.
     */
    protected function assertBackofficeLogFields(
        FundBackofficeLog $log,
        string $bsn,
        string $action,
        string $state,
    ): void {
        $this->assertSame($state, $log->state);

        if ($state === BackofficeApi::STATE_SUCCESS) {
            $this->assertSame($log->request_id, $log->response_body['id']);
        }

        $this->assertSame($action, $log->action);
        $this->assertSame($bsn, $log->bsn);
    }

    /**
     * @param FundBackofficeLog $log
     * @param string $bsn
     * @return void
     */
    protected function assertBackofficeReceivedLogSuccess(FundBackofficeLog $log, string $bsn): void
    {
        $this->assertBackofficeLogFields($log, $bsn, BackofficeApi::ACTION_REPORT_RECEIVED, BackofficeApi::STATE_SUCCESS);
    }

    /**
     * @param FundBackofficeLog $log
     * @param string $bsn
     * @return void
     */
    protected function assertBackofficeReceivedLogPending(FundBackofficeLog $log, string $bsn): void
    {
        $this->assertBackofficeLogFields($log, $bsn, BackofficeApi::ACTION_REPORT_RECEIVED, BackofficeApi::STATE_PENDING);
    }

    /**
     * @param FundBackofficeLog $log
     * @param string $bsn
     * @return void
     */
    protected function assertBackofficeFirstUseLogSuccess(FundBackofficeLog $log, string $bsn): void
    {
        $this->assertBackofficeLogFields($log, $bsn, BackofficeApi::ACTION_REPORT_FIRST_USE, BackofficeApi::STATE_SUCCESS);
    }

    /**
     * @param FundBackofficeLog $log
     * @param string $bsn
     * @return void
     */
    protected function assertBackofficeFirstUseLogPending(FundBackofficeLog $log, string $bsn): void
    {
        $this->assertBackofficeLogFields($log, $bsn, BackofficeApi::ACTION_REPORT_FIRST_USE, BackofficeApi::STATE_PENDING);
    }

    /**
     * Clears existing fake callbacks from the HTTP facade.
     *
     * This method uses reflection to access and reset the 'stubCallbacks' property
     * of the HTTP facade's root object. It catches any exceptions that may occur
     * during this process to prevent errors from propagating.
     */
    protected static function clearExistingFakes(): void
    {
        try {
            $reflection = new ReflectionObject(Http::getFacadeRoot());
            $property = $reflection->getProperty('stubCallbacks');
            $property->setValue(Http::getFacadeRoot(), collect());
        } catch (Throwable) {
        }
    }

    /**
     * Generates an array of test credentials with random strings.
     *
     * @return array{
     *    backoffice_certificate: string,
     *    backoffice_client_cert: string,
     *    backoffice_client_cert_key: string
     *  }
     */
    protected static function generateTestBackofficeCredentials(): array
    {
        return [
            'backoffice_key' => Str::random(64),
            'backoffice_certificate' => Str::random(64),
            'backoffice_client_cert' => Str::random(64),
            'backoffice_client_cert_key' => Str::random(64),
        ];
    }
}
