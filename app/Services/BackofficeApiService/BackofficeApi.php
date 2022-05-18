<?php


namespace App\Services\BackofficeApiService;

use App\Models\Fund;
use App\Models\FundBackofficeLog;
use App\Services\BackofficeApiService\Responses\EligibilityResponse;
use App\Services\BackofficeApiService\Responses\ResidencyResponse;
use App\Services\Forus\Record\Repositories\Interfaces\IRecordRepo;
use GuzzleHttp\Client;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

/**
 * Class BackofficeApi
 * @package App\Services\BackofficeApiService
 */
class BackofficeApi
{
    /** @var Fund */
    protected Fund $fund;
    protected IRecordRepo $recordRepo;

    public const ACTION_ELIGIBILITY_CHECK = 'eligibility_check';
    public const ACTION_RESIDENCY_CHECK = 'residency_check';

    public const ACTION_REPORT_FIRST_USE = 'first_use';
    public const ACTION_REPORT_RECEIVED = 'received';
    public const ACTION_STATUS = 'status';

    public const STATE_PENDING = 'pending';
    public const STATE_SUCCESS = 'success';
    public const STATE_ERROR = 'error';

    public const TOTAL_ATTEMPTS = 5;
    public const ATTEMPTS_INTERVAL = 8;

    /**
     * SponsorApi constructor.
     */
    public function __construct(IRecordRepo $recordRepo, Fund $fund)
    {
        $this->recordRepo = $recordRepo;
        $this->fund = $fund;
    }

    /**
     * Check BSN-number for eligibility
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
     * Report to the API that a voucher was received by identity
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
     * Report to the API that a voucher was used for the first time
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
     * @return Fund
     */
    public function getFund(): Fund
    {
        return $this->fund;
    }

    /**
     * Check API status
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

        return $log->updateModel(array_merge(Arr::only($response, [
            'response_code', 'response_error',
        ]), [
            'state' => self::STATE_ERROR,
        ]));
    }

    /**
     * Make the request to the API to check BSN-number residency
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
     * Make the request to the API to check BSN-number eligibility
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
        $log = $this->makeLog($action, $bsn);
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

        return $log->updateModel(array_merge(Arr::only($response, [
            'response_code', 'response_error',
        ]), [
            'state' => self::STATE_ERROR,
        ]));
    }

    /**
     * @return string
     */
    protected static function makeRequestId(): string
    {
        return "forus-" . token_generator_db(FundBackofficeLog::query(), 'response_id', 16);
    }

    /**
     * Add backoffice log
     *
     * @param string $action
     * @param string|null $bsn
     * @param string|null $requestId
     * @return FundBackofficeLog
     */
    protected function makeLog(
        string $action,
        ?string $bsn = null,
        ?string $requestId = null
    ): ?FundBackofficeLog {
        $identityAddress = $bsn ? $this->recordRepo->identityAddressByBsn($bsn) : null;

        if (!in_array($action, [self::ACTION_STATUS, self::ACTION_REPORT_FIRST_USE])) {
            $requestId = $requestId ?: self::makeRequestId();
        }

        /** @var FundBackofficeLog $fundLog */
        $fundLog = $this->fund->backoffice_logs()->create([
            'identity_address'  => $identityAddress,
            'bsn'               => $bsn,
            'action'            => $action,
            'request_id'        => $requestId,
            'response'          => null,
            'state'             => self::STATE_PENDING,
            'attempts'          => 0,
            'last_attempt_at'   => null,
        ]);

        return $fundLog ?? null;
    }

    /**
     * Make the request to the API
     *
     * @param string $method
     * @param string $url
     * @param array $data
     * @return array
     */
    public function request(string $method, string $url, array $data = []): array
    {
        $guzzleClient = new Client();
        $certTmpFile = (new TmpFile($this->fund->fund_config->backoffice_certificate));

        try {
            $options = $this->makeRequestOptions($method, $data);
            $options['verify'] = $certTmpFile->path();
            $response = $guzzleClient->request($method, $url, $options);
            $certTmpFile->close();

            return [
                'success' => true,
                'response_code'  => $response->getStatusCode(),
                'response_body' => json_decode($response->getBody()->getContents(), true),
            ];
        } catch (\Throwable $e) {
            $certTmpFile->close();

            return [
                'success' => false,
                'response_code' => $e->getCode(),
                'response_error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get API endpoints by action
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
            self::ACTION_STATUS => '/api/v1/status',
        ][$action] ?? abort(403);

        return rtrim($this->fund->fund_config->backoffice_url, '/') . $endpoint;
    }

    /**
     * Make request headers
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
     * Make request body
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
            "action" => $action,
            "fund_key" => $this->fund->fund_config->key,
        ];
    }

    /**
     * Make Guzzle request options
     *
     * @param string $method
     * @param array $data
     * @return array
     */
    protected function makeRequestOptions(string $method, array $data): array {
        return array_merge([
            'headers' => $this->makeRequestHeaders(),
            'connect_timeout' => config('forus.backoffice_api.connect_timeout', 10),
            'cert' => [
                config('forus.backoffice_api.cert_path'),
                config('forus.backoffice_api.cert_pass')
            ],
            'ssl_key' => [
                config('forus.backoffice_api.key_path'),
                config('forus.backoffice_api.key_pass')
            ],
        ], $method === 'GET' ? [
            'query' => $data,
        ]: [
            'json' => $data,
        ]);
    }

    /**
     * Get list of logs to be sent to the API
     *
     * @return FundBackofficeLog|Builder|\Illuminate\Database\Query\Builder
     */
    public static function getNextLogInQueueQuery()
    {
        return FundBackofficeLog::query()
            ->orderBy('created_at', 'ASC')
            ->where(function(Builder $builder) {
                $builder->where('action', self::ACTION_REPORT_RECEIVED);
                $builder->orWhere(function(Builder $builder) {
                    $builder->where('action', self::ACTION_REPORT_FIRST_USE);
                    $builder->whereHas('voucher.backoffice_log_received', function(Builder $builder) {
                        $builder->where('state', self::STATE_SUCCESS);
                        $builder->whereNotNull('response_id');
                    });
                });
            })
            ->where('state', '!=', self::STATE_SUCCESS)
            ->where('attempts', '<', self::TOTAL_ATTEMPTS)
            ->where(function(Builder $query) {
                $query->where('last_attempt_at', '<', now()->subHours(self::ATTEMPTS_INTERVAL));
                $query->orWhereNull('last_attempt_at');
            });
    }

    /**
     * Get next log int the queue to be sent to the API
     *
     * @return FundBackofficeLog|null
     */
    protected static function getNextLogInQueue(): ?FundBackofficeLog
    {
        return self::getNextLogInQueueQuery()->first();
    }

    /**
     * Send pending logs to the API
     *
     * @return void
     */
    public static function sendLogs(): void
    {
        while ($log = self::getNextLogInQueue()) {
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
                $log->updateModel(array_merge(Arr::only($response, [
                    'response_code', 'response_error',
                ]), [
                    'state' => self::STATE_ERROR,
                ]));
            }
        }
    }
}