<?php


namespace App\Services\SponsorApiService;


use App\Models\Fund;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class SponsorApi
{
    protected $guzzleClient;

    protected $requiredPemFiles = ['server-ca-crt.pem', 'client-crt.pem', 'client-key.pem'];

    protected const PEM_PASSPHRASE = 'password';

    protected const ACTION_ELIGIBILITY_CHECK = 'eligibility_check';

    protected const STATE_PENDING = 'pending';
    protected const STATE_SUCCESS = 'success';
    protected const STATE_ERROR = 'error';


    /**
     * SponsorApi constructor.
     */
    public function __construct()
    {
        $this->guzzleClient = new Client();
    }

    /**
     * @param Fund $fund
     * @param string $bsn
     */
    public function eligibilityCheck(Fund $fund, string $bsn)
    {
        $endpoint = '/api/v1/funds';
        $identityAddress = resolve('forus.services.record')
            ->identityAddressByBsn($bsn);

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
                "action" => self::ACTION_ELIGIBILITY_CHECK,
                "fund_key"=> $fund->fund_config->key
            ];

            try {
                $response = $this->guzzleClient->post(
                    $url . $endpoint, [
                        'json' => $content,
                        'headers' => $headers,
                        'connect_timeout' => 650,
                        'verify' => storage_path('app/sponsor-certificates/server-ca-crt.pem'),
                        'cert' => [storage_path('app/sponsor-certificates/client-crt.pem'), self::PEM_PASSPHRASE],
                        'ssl_key' => [storage_path('app/sponsor-certificates/client-key.pem'), self::PEM_PASSPHRASE],
                    ]
                );

                if (in_array($response->getStatusCode(), [200, 201])) {

                    $responseArr = json_decode(
                        $response->getBody()->getContents(),
                        true
                    );

                    if (isset($responseArr['eligible']) && $responseArr['eligible']) {
                        $fund->makeVoucher($identityAddress);
                        $fund->fund_logs()->create([
                            'identity_address'  => $identityAddress,
                            'identity_bsn'      => $bsn,
                            'action'            => self::ACTION_ELIGIBILITY_CHECK,
                            'response_id'       => $responseArr['id'] ?? null,
                            'state'             => self::STATE_SUCCESS,
                            'attempts'          => 1,
                            'last_attempt_at'   => Carbon::now(),
                        ]);
                    }
                }

            } catch (GuzzleException $e) {
                $fund->fund_logs()->create([
                    'identity_address'  => $identityAddress,
                    'identity_bsn'      => $bsn,
                    'action'            => self::ACTION_ELIGIBILITY_CHECK,
                    'state'             => self::STATE_ERROR,
                    'error_code'        => $e->getCode(),
                    'error_message'     => $e->getMessage(),
                    'attempts'          => 1,
                    'last_attempt_at'   => Carbon::now(),
                ]);
            }

        }
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