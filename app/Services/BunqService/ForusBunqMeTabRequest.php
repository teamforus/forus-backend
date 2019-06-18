<?php


namespace App\Services\BunqService;


use bunq\Model\Generated\Endpoint\BunqMeTab;
use GuzzleHttp\Client;

class ForusBunqMeTabRequest
{
    private $bunqMeTab;
    protected $bunqUseSandbox;
    protected $bunqApiUrl;

    const API_PRODUCTION = "https://api.bunq.me/v1/";
    const API_SANDBOX = "https://api-bunqme.sandbox.bunq.com/v1/";

    const API_HEADERS = [
        'X-Bunq-Client-Request-Id' => '',
        'X-Bunq-Language' => 'en_US',
    ];

    /**
     * ForusBunqMeTabRequest constructor.
     * @param bool $bunqUseSandbox
     * @param BunqMeTab $bunqMeTab
     */
    public function __construct(
        bool $bunqUseSandbox,
        BunqMeTab $bunqMeTab
    ) {
        $this->bunqMeTab = $bunqMeTab;
        $this->bunqApiUrl = self::getApiUrl($bunqUseSandbox);
        $this->bunqUseSandbox = $bunqUseSandbox;
    }

    /**
     * @return string
     */
    public function getShareUrl(): string {
        return $this->bunqMeTab->getBunqmeTabShareUrl();
    }

    /**
     * @return string
     */
    public function getUuid(): string {
        return $this->bunqMeTab->getBunqmeTabEntry()->getUuid();
    }

    /**
     * @return ForusQrCodeImage
     */
    public function getBunqMeTabQrCodeImage(): ForusQrCodeImage
    {
        $apiUrl = sprintf(
            "%s/%s/%s/%s",
            $this->bunqApiUrl,
            "bunqme-tab-entry",
            $this->getUuid(),
            "qr-code-content"
        );

        $request = [
            'headers' => self::API_HEADERS
        ];

        $client = new Client();

        $data = $client->post($apiUrl, $request);
        $uuid = json_decode(
            $data->getBody()->getContents()
        )->Response[0]->Uuid->uuid;

        $data = $client->get("$apiUrl/$uuid", $request);

        return ForusQrCodeImage::makeFromObject(json_decode(
            $data->getBody()->getContents()
        )->Response[0]->QrCodeImage);
    }

    /**
     * @param $issuer
     * @return BunqMeMerchantRequest|null
     * @throws \Exception
     */
    public function makeIdealIssuerRequest($issuer) {
        $apiUrl = sprintf(
            "%s/%s",
            $this->bunqApiUrl,
            "bunqme-merchant-request"
        );

        $amount = $this->bunqMeTab->getBunqmeTabEntry()->getAmountInquired();
        $request = [
            'headers' => self::API_HEADERS,
            'json' => [
                'amount_requested'  => $amount,
                "bunqme_type"       => "TAB",
                "issuer"            => $issuer,
                "merchant_type"     => "IDEAL",
                "bunqme_uuid"       => $this->getUuid()
            ]
        ];

        try {
            $data = (new Client())->post($apiUrl, $request);
            $uuid = json_decode(
                $data->getBody()->getContents()
            )->Response[0]->BunqMeMerchantRequest->uuid;

            unset($request['json']);

            $tries = 5;

            do {
                if ($tries-- <= 0) {
                    throw new \Exception(
                        "Could not create payment request to issuer", 503);
                }

                sleep(1);

                $data = (new Client())->get("$apiUrl/$uuid", $request);

                $bunqMeMerchantRequest = json_decode(
                    $data->getBody()->getContents()
                )->Response[0]->BunqMeMerchantRequest;

            } while(is_null($bunqMeMerchantRequest->issuer_authentication_url));

            return BunqMeMerchantRequest::makeFromObject(
                $bunqMeMerchantRequest
            );
        } catch (\Exception $exception) {
            throw new \Exception(
                join("\n", [
                    "Could not create payment request to issuer:",
                    $exception->getMessage()
                ]), 503
            );
        }
    }

    /**
     * @param bool $sandbox
     * @return string
     */
    public static function getApiUrl(bool $sandbox) {
        return $sandbox ? self::API_SANDBOX : self::API_PRODUCTION;
    }

    /**
     * @return BunqMeTab
     */
    public function getBunqMeTab(): BunqMeTab
    {
        return $this->bunqMeTab;
    }
}