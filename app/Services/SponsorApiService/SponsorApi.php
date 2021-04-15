<?php


namespace App\Services\SponsorApiService;


use App\Models\Fund;
use App\Models\FundLog;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Database\Eloquent\Builder;

class SponsorApi
{
    protected $guzzleClient;
    protected $serviceRecord;

    protected $requiredPemFiles = ['server-ca-crt.pem', 'client-crt.pem', 'client-key.pem'];

    protected $errorActionsToRetry = ['received', 'first_use'];

    protected const ENDPOINT = '/api/v1/funds';

    protected const PEM_PASSPHRASE = 'password';

    protected const ACTION_ELIGIBILITY_CHECK = 'eligibility_check';
    protected const ACTION_REPORT_RECEIVED = 'received';
    protected const ACTION_REPORT_FIRST_USE = 'first_use';

    protected const STATE_SUCCESS = 'success';
    protected const STATE_ERROR = 'error';

    /**
     * SponsorApi constructor.
     */
    public function __construct()
    {
        $this->guzzleClient = new Client();
        $this->serviceRecord = resolve('forus.services.record');
    }

    /**
     * @param Fund $fund
     * @param string $bsn
     * @return array
     */
    public function eligibilityCheck(Fund $fund, string $bsn): array
    {
        $result = $this->requestApi($fund, $bsn, self::ACTION_ELIGIBILITY_CHECK);
        $identityAddress = $this->serviceRecord->identityAddressByBsn($bsn);

        if (count($result) && isset($result['eligible']) && $result['eligible']) {
            $fund->makeVoucher($identityAddress);
            $this->reportReceived($fund, $bsn);
        }

        return $result;
    }

    /**
     * @param Fund $fund
     * @param string $bsn
     * @return array
     */
    public function reportReceived(Fund $fund, string $bsn): array
    {
        return $this->requestApi($fund, $bsn, self::ACTION_REPORT_RECEIVED);
    }

    /**
     * @param Fund $fund
     * @param string $bsn
     * @return array
     */
    public function reportFirstUse(Fund $fund, string $bsn): array
    {
        return $this->requestApi($fund, $bsn, self::ACTION_REPORT_FIRST_USE);
    }

    /**
     * @param Fund $fund
     * @param string $bsn
     * @param string $action
     * @param FundLog|null $log
     * @return array
     */
    public function requestApi(Fund $fund, string $bsn, string $action, FundLog $log = null): array
    {
        $result = [];
        $identityAddress = $this->serviceRecord->identityAddressByBsn($bsn);

        if ($fund->isSponsorApiConfigured() && $this->certificateExists() && $bsn) {
            $url = $fund->fund_config->sponsor_api_url;
            $token = $fund->fund_config->sponsor_api_token;
            $headers = [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ];

            $content = [
                "bsn"=> $bsn,
                "action" => $action,
                "fund_key"=> $fund->fund_config->key
            ];

            try {
                $response = $this->guzzleClient->post(
                    $url . self::ENDPOINT, [
                        'json' => $content,
                        'headers' => $headers,
                        'connect_timeout' => 650,
                        'verify' => storage_path('app/sponsor-certificates/server-ca-crt.pem'),
                        'cert' => [storage_path('app/sponsor-certificates/client-crt.pem'), self::PEM_PASSPHRASE],
                        'ssl_key' => [storage_path('app/sponsor-certificates/client-key.pem'), self::PEM_PASSPHRASE],
                    ]
                );

                if (in_array($response->getStatusCode(), [200, 201])) {

                    $result = json_decode(
                        $response->getBody()->getContents(),
                        true
                    );

                    $logSuccess = [
                        'identity_address'  => $identityAddress ?? 0,
                        'identity_bsn'      => $bsn,
                        'action'            => $action,
                        'response_id'       => $result['id'] ?? null,
                        'state'             => self::STATE_SUCCESS,
                        'attempts'          => $log->exists ? $log->attempts + 1 : 1,
                        'last_attempt_at'   => Carbon::now(),
                    ];
                    $log->exists
                        ? $log->update($logSuccess)
                        : $fund->fund_logs()->create($logSuccess);

                }

            } catch (GuzzleException $e) {
                $logError = [
                    'identity_address'  => $identityAddress ?? 0,
                    'identity_bsn'      => $bsn,
                    'action'            => $action,
                    'state'             => self::STATE_ERROR,
                    'error_code'        => $e->getCode(),
                    'error_message'     => $e->getMessage(),
                    'attempts'          => $log->exists ? $log->attempts + 1 : 1,
                    'last_attempt_at'   => Carbon::now(),
                ];

                $log->exists
                    ? $log->update($logError)
                    : $fund->fund_logs()->create($logError);
            }
        }

        return $result;
    }

    /**
     * @return bool
     */
    public function certificateExists(): bool
    {
        $missingPemFiles = array_filter($this->requiredPemFiles, function($pemFile) {
            return !file_exists(storage_path('app/sponsor-certificates/' . $pemFile));
        });

        return count($missingPemFiles) == 0;
    }

    public function getNextErrorLogInQueueQuery()
    {
        return FundLog::query()->orderBy('updated_at', 'ASC')
            ->whereIn('action', $this->errorActionsToRetry)
            ->where('state', '=', self::STATE_ERROR)
            ->where('attempts', '<', 5)
            ->where(function(Builder $query) {
                $query->whereNull('last_attempt_at')->orWhere(
                    'last_attempt_at', '<', Carbon::now()->subHours(8)
                );
            });
    }

    /**
     * @return FundLog|null
     */
    public function getNextErrorLogInQueue(): ?FundLog
    {
        /** @var  FundLog $errorLog */
        if (!$errorLog = $this->getNextErrorLogInQueueQuery()->first()) {
            return null;
        }

        return $errorLog;
    }

    public function retryActionsFromErrorLogs() {
        /** @var FundLog $errorLog */
        while ($errorLog = $this->getNextErrorLogInQueue()) {
            $this->requestApi(
                Fund::find($errorLog->fund_id),
                $errorLog->identity_bsn,
                $errorLog->action,
                $errorLog
            );
        }
    }


}