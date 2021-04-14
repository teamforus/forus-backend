<?php


namespace App\Services\SponsorApiService;


use App\Models\Fund;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class SponsorApi
{
    protected $guzzleClient;
    protected $serviceRecord;

    protected $requiredPemFiles = ['server-ca-crt.pem', 'client-crt.pem', 'client-key.pem'];

    protected const ENDPOINT = '/api/v1/funds';

    protected const PEM_PASSPHRASE = 'password';

    protected const ACTION_ELIGIBILITY_CHECK = 'eligibility_check';
    protected const ACTION_REPORT_RECEIVED = 'received';
    protected const ACTION_REPORT_FIRST_USE = 'first_use';

    protected const STATE_PENDING = 'pending';
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
     * @return array|mixed
     */
    public function eligibilityCheck(Fund $fund, string $bsn)
    {
        $result = $this->requestApi($fund, $bsn, self::ACTION_ELIGIBILITY_CHECK);
        $identityAddress = $this->serviceRecord->identityAddressByBsn($bsn);

        if (count($result) && isset($result['eligible']) && $result['eligible']) {
            $fund->makeVoucher($identityAddress);
        }

        return $result;
    }

    /**
     * @param Fund $fund
     * @param string $bsn
     * @return array|mixed
     */
    public function reportReceived(Fund $fund, string $bsn)
    {
        return $this->requestApi($fund, $bsn, self::ACTION_REPORT_RECEIVED);
    }

    /**
     * @param Fund $fund
     * @param string $bsn
     * @return array|mixed
     */
    public function reportFirstUse(Fund $fund, string $bsn)
    {
        return $this->requestApi($fund, $bsn, self::ACTION_REPORT_FIRST_USE);
    }

    /**
     * @param Fund $fund
     * @param string $bsn
     * @param string $action
     * @return array|mixed
     */
    public function requestApi(Fund $fund, string $bsn, string $action): array
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

                    $fund->fund_logs()->create([
                        'identity_address'  => $identityAddress ?? 1,
                        'identity_bsn'      => $bsn,
                        'action'            => $action,
                        'response_id'       => $result['id'] ?? null,
                        'state'             => self::STATE_SUCCESS,
                        'attempts'          => 1,
                        'last_attempt_at'   => Carbon::now(),
                    ]);

                }

            } catch (GuzzleException $e) {
                $fund->fund_logs()->create([
                    'identity_address'  => $identityAddress ?? 1,
                    'identity_bsn'      => $bsn,
                    'action'            => $action,
                    'state'             => self::STATE_ERROR,
                    'error_code'        => $e->getCode(),
                    'error_message'     => $e->getMessage(),
                    'attempts'          => 1,
                    'last_attempt_at'   => Carbon::now(),
                ]);
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
}