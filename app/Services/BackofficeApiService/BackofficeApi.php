<?php

namespace App\Services\BackofficeApiService;

use App\Models\Fund;
use App\Models\FundBackofficeLog;
use App\Models\Identity;
use App\Services\BackofficeApiService\Responses\EligibilityResponse;
use App\Services\BackofficeApiService\Responses\PartnerBsnResponse;
use App\Services\BackofficeApiService\Responses\ResidencyResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class BackofficeApi
{
    public const string ACTION_ELIGIBILITY_CHECK = 'eligibility_check';
    public const string ACTION_RESIDENCY_CHECK = 'residency_check';

    public const string ACTION_REPORT_FIRST_USE = 'first_use';
    public const string ACTION_REPORT_RECEIVED = 'received';
    public const string ACTION_STATUS = 'status';

    public const string ACTION_PARTNER_BSN = 'partner_bsn';

    public const string STATE_PENDING = 'pending';
    public const string STATE_SUCCESS = 'success';
    public const string STATE_ERROR = 'error';

    public const int TOTAL_ATTEMPTS = 5;
    public const int ATTEMPTS_INTERVAL = 8;

    protected Fund $fund;

    /**
     * @param Fund $fund
     */
    public function __construct(Fund $fund)
    {
        $this->fund = $fund;
    }

    /**
     * Check BSN-number for eligibility.
     *
     * @param string $bsn
     * @param string|null $requestId
     * @return EligibilityResponse
     */
    public function eligibilityCheck(string $bsn, ?string $requestId = null): EligibilityResponse
    {
        return new EligibilityResponse($this->checkEligibility($bsn, $requestId ?: self::makeRequestId()));
    }

    /**
     * @param string $bsn
     * @return ResidencyResponse
     */
    public function residencyCheck(string $bsn): ResidencyResponse
    {
        return new ResidencyResponse($this->checkResidency($bsn, self::makeRequestId()));
    }

    /**
     * @param string $bsn
     * @return PartnerBsnResponse
     */
    public function partnerBsn(string $bsn): PartnerBsnResponse
    {
        return new PartnerBsnResponse($this->getPartnerBsn($bsn, self::makeRequestId()));
    }

    /**
     * Report to the API that a voucher was received by identity.
     *
     * @param string $bsn
     * @param string|null $requestId
     * @return FundBackofficeLog
     */
    public function reportReceived(string $bsn, ?string $requestId = null): FundBackofficeLog
    {
        return $this->makeLog(self::ACTION_REPORT_RECEIVED, $bsn, $requestId);
    }

    /**
     * Report to the API that a voucher was used for the first time.
     *
     * @param string $bsn
     * @param string|null $requestId
     * @return FundBackofficeLog
     */
    public function reportFirstUse(string $bsn, ?string $requestId = null): FundBackofficeLog
    {
        return $this->makeLog(self::ACTION_REPORT_FIRST_USE, $bsn, $requestId);
    }

    /**
     * Check API status.
     *
     * @return FundBackofficeLog
     */
    public function checkStatus(): FundBackofficeLog
    {
        $log = $this->makeLog(self::ACTION_STATUS);
        $response = $this->request('GET', $this->getEndpoint(self::ACTION_STATUS));

        if ($response['success'] ?? false) {
            return $log->updateModel(array_merge(Arr::only($response, [
                'response_code', 'response_body',
            ]), [
                'state' => self::STATE_SUCCESS,
            ]));
        }

        self::logError(sprintf(
            "\nID: $log->id\nAction: $log->action\nResponseCode: %s\nResponseError: %s\nResponseBody: %s",
            Arr::get($response, 'response_code', ''),
            Arr::get($response, 'response_error', ''),
            json_encode(Arr::get($response, 'response_body', ''), JSON_PRETTY_PRINT),
        ));

        return $log->updateModel(array_merge(Arr::only($response, [
            'response_code', 'response_error',
        ]), [
            'state' => self::STATE_ERROR,
        ]));
    }

    /**
     * Make the request to the API.
     *
     * @param string $method
     * @param string $url
     * @param array $data
     * @return array
     */
    public function request(string $method, string $url, array $data = []): array
    {
        $certTmpFile = new TmpFile($this->fund->fund_config->backoffice_certificate);
        $clientCertTmpFile = new TmpFile($this->fund->fund_config->backoffice_client_cert);
        $clientCertKeyTmpFile = new TmpFile($this->fund->fund_config->backoffice_client_cert_key);

        try {
            $http = Http::withHeaders($this->makeRequestHeaders())
                ->timeout(10)
                ->withOptions([
                    'verify' => $certTmpFile->path(),
                    'cert' => $clientCertTmpFile->path(),
                    'ssl_key' => $clientCertKeyTmpFile->path(),
                ]);

            if ($method === 'GET') {
                $response = $http->get($url, $data);
            } else {
                $response = $http->{$method}($url, $data);
            }

            if ($response->failed()) {
                return [
                    'success' => false,
                    'response_code' => $response->status(),
                    'response_error' => null,
                    'response_body' => $response->json(),
                ];
            }

            return [
                'success' => true,
                'response_code' => $response->status(),
                'response_body' => $response->json(),
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'response_code' => $e->getCode(),
                'response_error' => $e->getMessage(),
                'response_body' => null,
            ];
        } finally {
            $certTmpFile->close();
            $clientCertTmpFile->close();
            $clientCertKeyTmpFile->close();
        }
    }

    /**
     * Get API endpoints by action.
     *
     * @param string $action
     * @return string
     */
    public function getEndpoint(string $action): string
    {
        $endpoint = [
            self::ACTION_ELIGIBILITY_CHECK => '/api/v1/funds',
            self::ACTION_REPORT_FIRST_USE => '/api/v1/funds',
            self::ACTION_REPORT_RECEIVED => '/api/v1/funds',
            self::ACTION_RESIDENCY_CHECK => '/api/v1/funds',
            self::ACTION_PARTNER_BSN => '/api/v1/funds',
            self::ACTION_STATUS => '/api/v1/status',
        ][$action] ?? abort(403);

        return rtrim($this->fund->fund_config->backoffice_url, '/') . $endpoint;
    }

    /**
     * Make request headers.
     *
     * @return string[]
     */
    public function makeRequestHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->fund->fund_config->backoffice_key,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    /**
     * Get list of logs to be sent to the API.
     *
     * @param array $fundsId
     * @return FundBackofficeLog|Builder
     */
    public static function getNextLogInQueueQuery(array $fundsId = []): FundBackofficeLog|Builder
    {
        return FundBackofficeLog::where([])
            ->whereIn('fund_id', $fundsId)
            ->orderBy('created_at', 'ASC')
            ->where(function (Builder $builder) {
                $builder->where('action', self::ACTION_REPORT_RECEIVED);
                $builder->orWhere(function (Builder $builder) {
                    $builder->where('action', self::ACTION_REPORT_FIRST_USE);
                    $builder->whereHas('voucher.backoffice_log_received', function (Builder $builder) {
                        $builder->where('state', self::STATE_SUCCESS);
                        $builder->whereNotNull('response_id');
                    });
                });
            })
            ->where('state', '!=', self::STATE_SUCCESS)
            ->where('attempts', '<', self::TOTAL_ATTEMPTS)
            ->where(function (Builder $query) {
                $query->where('last_attempt_at', '<', now()->subHours(self::ATTEMPTS_INTERVAL));
                $query->orWhereNull('last_attempt_at');
            });
    }

    /**
     * Send pending logs to the API.
     *
     * @return void
     */
    public static function sendLogs(): void
    {
        $funds = Fund::get()->filter(fn (Fund $fund) => $fund->isBackofficeApiAvailable());

        while ($log = self::getNextLogInQueue($funds->pluck('id')->toArray())) {
            if (!$backofficeApi = $log->fund->getBackofficeApi()) {
                continue;
            }

            $log->increaseAttempts();

            $body = $backofficeApi->makeRequestBody($log->action);
            $endpoint = $backofficeApi->getEndpoint($log->action);

            if ($log->action === self::ACTION_REPORT_FIRST_USE) {
                $requestId = $log->voucher->backoffice_log_received->response_id ?? self::makeRequestId();
            } else {
                $requestId = $log->request_id;
            }

            if ($log->action === self::ACTION_REPORT_RECEIVED) {
                $body['eligible'] = $log->voucher->backoffice_log_eligible->response_body['eligible'] ?? false;
            }

            $response = $backofficeApi->request('POST', $endpoint, array_merge($body, [
                'id' => $requestId,
                'bsn' => $log->bsn,
            ]));

            if ($response['success'] ?? false) {
                $log->updateModel([
                    'state' => self::STATE_SUCCESS,
                    'request_id' => $requestId,
                    'response_id' => $response['response_body']['id'] ?? $requestId,
                    'response_body' => $response['response_body'],
                    'response_code' => $response['response_code'],
                ]);
            } else {
                self::logError(sprintf(
                    "\nID: $log->id\nAction: $log->action\nResponseCode: %s\nResponseError: %s\nResponseBody: %s",
                    Arr::get($response, 'response_code', ''),
                    Arr::get($response, 'response_error', ''),
                    json_encode(Arr::get($response, 'response_body', ''), JSON_PRETTY_PRINT),
                ));

                $log->updateModel(array_merge(Arr::only($response, [
                    'response_code', 'response_error',
                ]), [
                    'state' => self::STATE_ERROR,
                ]));
            }
        }
    }

    /**
     * @param string $message
     * @param Throwable|null $e
     * @return void
     */
    public static function logError(string $message, ?Throwable $e = null): void
    {
        Log::channel('backoffice')->error(implode("\n", array_filter([
            $message,
            $e?->getMessage(),
            $e?->getTraceAsString(),
        ])));
    }

    /**
     * Make the request to the API to check BSN-number residency.
     *
     * @param string $bsn
     * @param string|null $requestId
     * @return FundBackofficeLog
     */
    protected function checkResidency(string $bsn, ?string $requestId): FundBackofficeLog
    {
        return $this->makeCheckRequest(self::ACTION_RESIDENCY_CHECK, $bsn, $requestId);
    }

    /**
     * Make the request to the API to check BSN-number eligibility.
     *
     * @param string $bsn
     * @param string|null $requestId
     * @return FundBackofficeLog
     */
    protected function checkEligibility(string $bsn, ?string $requestId): FundBackofficeLog
    {
        return $this->makeCheckRequest(self::ACTION_ELIGIBILITY_CHECK, $bsn, $requestId);
    }

    /**
     * Make the request to the API to check BSN-number residency.
     *
     * @param string $bsn
     * @param string|null $requestId
     * @return FundBackofficeLog
     */
    protected function getPartnerBsn(string $bsn, ?string $requestId): FundBackofficeLog
    {
        return $this->makeCheckRequest(self::ACTION_PARTNER_BSN, $bsn, $requestId);
    }

    /**
     * @param string $action
     * @param string $bsn
     * @param string|null $requestId
     * @return FundBackofficeLog
     */
    protected function makeCheckRequest(
        string $action,
        string $bsn,
        ?string $requestId
    ): FundBackofficeLog {
        $log = $this->makeLog($action, $bsn, $requestId);
        $endpoint = $this->getEndpoint($action);
        $body = $this->makeRequestBody($action);

        $response = $this->request('POST', $endpoint, array_merge($body, [
            'id' => $requestId,
            'bsn' => $bsn,
        ]));

        if ($response['success'] ?? false) {
            return $log->updateModel(array_merge(Arr::only($response, [
                'response_code', 'response_body',
            ]), [
                'state' => self::STATE_SUCCESS,
                'response_id' => ($response['response_body']['id'] ?? null) ?: $log->request_id,
            ]));
        }

        self::logError(sprintf(
            "\nID: $log->id\nAction: $log->action\nResponseCode: %s\nResponseError: %s\nResponseBody: %s",
            Arr::get($response, 'response_code', ''),
            Arr::get($response, 'response_error', ''),
            json_encode(Arr::get($response, 'response_body', []), JSON_PRETTY_PRINT),
        ));

        return $log->updateModel(array_merge(Arr::only($response, [
            'response_code', 'response_error', 'response_body',
        ]), [
            'state' => self::STATE_ERROR,
        ]));
    }

    /**
     * @return string
     */
    protected static function makeRequestId(): string
    {
        do {
            $value = token_generator()->generate(16);
        } while (FundBackofficeLog::where('response_id', $value)->exists());

        return 'forus-' . $value;
    }

    /**
     * Add backoffice log.
     *
     * @param string $action
     * @param string|null $bsn
     * @param string|null $requestId
     * @return FundBackofficeLog|null
     */
    protected function makeLog(
        string $action,
        ?string $bsn = null,
        ?string $requestId = null
    ): ?FundBackofficeLog {
        if (!in_array($action, [self::ACTION_STATUS, self::ACTION_REPORT_FIRST_USE])) {
            $requestId = $requestId ?: self::makeRequestId();
        }

        /** @var FundBackofficeLog $fundLog */
        $fundLog = $this->fund->backoffice_logs()->create([
            'identity_address' => Identity::findByBsn($bsn)?->address,
            'bsn' => $bsn,
            'action' => $action,
            'request_id' => $requestId,
            'response' => null,
            'state' => self::STATE_PENDING,
            'attempts' => 0,
            'last_attempt_at' => null,
        ]);

        return $fundLog ?? null;
    }

    /**
     * Make request body.
     *
     * @param string $action
     * @return array
     */
    protected function makeRequestBody(string $action): array
    {
        if ($action === self::ACTION_STATUS) {
            return [];
        }

        return [
            'action' => $action,
            'fund_key' => $this->fund->fund_config->key,
        ];
    }

    /**
     * Get next log int the queue to be sent to the API.
     *
     * @param array $fundsId
     * @return FundBackofficeLog|null
     */
    protected static function getNextLogInQueue(array $fundsId = []): ?FundBackofficeLog
    {
        return self::getNextLogInQueueQuery($fundsId)->first();
    }
}
