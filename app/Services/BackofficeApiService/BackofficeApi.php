<?php


namespace App\Services\BackofficeApiService;

use App\Models\Fund;
use App\Models\FundBackofficeLog;
use App\Services\BackofficeApiService\Responses\EligibilityResponse;
use App\Services\BackofficeApiService\Responses\ResidencyResponse;
use App\Services\Forus\Record\Repositories\Interfaces\IRecordRepo;
use GuzzleHttp\Client;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class BackofficeApi
 * @package App\Services\BackofficeApiService
 */
class BackofficeApi
{
    /** @var Fund */
    protected $fund;
    protected $recordRepo;

    protected const LOG_ACTIONS = [
        'received', 'first_use'
    ];

    public const ACTION_ELIGIBILITY_CHECK = 'eligibility_check';
    public const ACTION_RESIDENCY_CHECK = 'residency_check';

    public const ACTION_REPORT_FIRST_USE = 'first_use';
    public const ACTION_REPORT_RECEIVED = 'received';
    public const ACTION_STATUS = 'status';

    public const STATE_PENDING = 'pending';
    public const STATE_SUCCESS = 'success';
    public const STATE_ERROR = 'error';

    /**
     * SponsorApi constructor.
     */
    public function __construct(IRecordRepo $recordRepo, Fund $fund)
    {
        $this->recordRepo = $recordRepo;
        $this->fund = $fund;
    }

    /**
     * @return Fund
     */
    public function getFund(): Fund
    {
        return $this->fund;
    }

    /**
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
     * @return string[]
     */
    public function getRequestHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->fund->fund_config->backoffice_key,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    /**
     * @param string $bsn
     * @return EligibilityResponse
     */
    public function eligibilityCheck(string $bsn): EligibilityResponse
    {
        return new EligibilityResponse($this->checkEligibility($bsn));
    }

    /**
     * @param string $bsn
     * @return ResidencyResponse
     */
    public function residencyCheck(string $bsn): ResidencyResponse
    {
        return new ResidencyResponse($this->checkResidency($bsn));
    }

    /**
     * @return FundBackofficeLog
     */
    public function checkStatus(): FundBackofficeLog
    {
        $log = $this->makeLog(self::ACTION_STATUS);
        $response = $this->request('GET', $this->getEndpoint(self::ACTION_STATUS));
        $success = $response['success'] ?? false;

        if ($success) {
            return $log->updateModel(array_merge(array_only($response, [
                'response_code', 'response_body',
            ]), [
                'state' => self::STATE_SUCCESS,
            ]));
        }

        return $log->updateModel(array_merge(array_only($response, [
            'response_code', 'response_error',
        ]), [
            'state' => self::STATE_ERROR,
        ]));
    }

    /**
     * @param string $bsn
     * @return FundBackofficeLog
     */
    protected function checkEligibility(string $bsn): FundBackofficeLog
    {
        $log = $this->makeLog(self::ACTION_ELIGIBILITY_CHECK, $bsn);
        $endpoint = $this->getEndpoint(self::ACTION_ELIGIBILITY_CHECK);
        $body = $this->getRequestBody(self::ACTION_ELIGIBILITY_CHECK);
        $result = $this->request('POST', $endpoint, array_merge($body, compact('bsn')));

        if (!($result['success'] ?? false)) {
            return $log->updateModel(array_merge(array_only($result, [
                'response_code', 'response_error',
            ]), [
                'state' => self::STATE_ERROR,
            ]));
        }

        return $log->updateModel(array_merge(array_only($result, [
            'response_code', 'response_body',
        ]), [
            'response_id' => $result['response_body']['id'] ?? null,
            'state' => self::STATE_SUCCESS,
        ]));
    }

    /**
     * @param string $bsn
     * @return FundBackofficeLog
     */
    protected function checkResidency(string $bsn): FundBackofficeLog
    {
        $log = $this->makeLog(self::ACTION_RESIDENCY_CHECK, $bsn);
        $endpoint = $this->getEndpoint(self::ACTION_RESIDENCY_CHECK);
        $body = $this->getRequestBody(self::ACTION_RESIDENCY_CHECK);
        $result = $this->request('POST', $endpoint, array_merge($body, compact('bsn')));

        if (!($result['success'] ?? false)) {
            return $log->updateModel(array_merge(array_only($result, [
                'response_code', 'response_error',
            ]), [
                'state' => self::STATE_ERROR,
            ]));
        }

        return $log->updateModel(array_merge(array_only($result, [
            'response_code', 'response_body',
        ]), [
            'response_id' => $result['response_body']['id'] ?? null,
            'state' => self::STATE_SUCCESS,
        ]));
    }

    /**
     * @param string $bsn
     * @return FundBackofficeLog
     */
    public function reportReceived(string $bsn): FundBackofficeLog
    {
        return $this->makeLog(self::ACTION_REPORT_RECEIVED, $bsn);
    }

    /**
     * @param string $bsn
     * @return FundBackofficeLog
     */
    public function reportFirstUse(string $bsn): FundBackofficeLog
    {
        return $this->makeLog(self::ACTION_REPORT_FIRST_USE, $bsn);
    }

    /**
     * @param string $action
     * @param string|null $bsn
     * @return FundBackofficeLog
     */
    private function makeLog(string $action, string $bsn = null): ?FundBackofficeLog
    {
        $identityAddress = $bsn ? $this->recordRepo->identityAddressByBsn($bsn) : null;

        /** @var FundBackofficeLog $fundLog */
        $fundLog = $this->fund->backoffice_logs()->create([
            'identity_address'  => $identityAddress,
            'bsn'               => $bsn,
            'action'            => $action,
            'response_id'       => null,
            'response'          => null,
            'state'             => self::STATE_PENDING,
            'attempts'          => 0,
            'last_attempt_at'   => null,
        ]);

        return $fundLog ?? null;
    }

    /**
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
     * @param string $method
     * @param array $data
     * @return array
     */
    protected function makeRequestOptions(string $method, array $data): array {
        return array_merge([
            'headers' => $this->getRequestHeaders(),
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
     * @param string $action
     * @return array
     */
    private function getRequestBody(string $action): array
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
     * @return FundBackofficeLog|Builder|\Illuminate\Database\Query\Builder
     */
    protected static function getNextLogInQueueQuery()
    {
        return FundBackofficeLog::query()->orderBy('updated_at', 'ASC')
            ->whereIn('action', self::LOG_ACTIONS)
            ->where('state', '=', self::STATE_PENDING)
            ->where('attempts', '<', 5)
            ->where(function(Builder $query) {
                $query->whereNull('last_attempt_at');
                $query->orWhere('last_attempt_at', '<', now()->subHours(8));
            });
    }

    /**
     * @return FundBackofficeLog|null
     */
    protected static function getNextLogInQueue(): ?FundBackofficeLog
    {
        return self::getNextLogInQueueQuery()->first();
    }

    /**
     * @return void
     */
    public static function sendLogs(): void
    {
        while ($log = self::getNextLogInQueue()) {
            if (!$backofficeApi = $log->fund->getBackofficeApi()) {
                continue;
            }

            $log->increaseAttempts();

            $body = $backofficeApi->getRequestBody($log->action);
            $endpoint = $backofficeApi->getEndpoint($log->action);

            $response = $backofficeApi->request('POST', $endpoint, array_merge($body, [
                'bsn' => $log->bsn,
            ]));

            if ($response && $response['success']) {
                $log->update([
                    'state' => self::STATE_SUCCESS,
                    'response_id' => $response['response_body']['id'] ?? null,
                    'response_body' => $response['response_body'],
                    'response_code' => $response['response_code'],
                ]);
            }
        }
    }
}