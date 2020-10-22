<?php

namespace App\Services\KvkApiService;

use Illuminate\Support\Collection;

/**
 * Class KvkApi
 * @package App\Services\KvkApiService
 */
class KvkApi
{
    protected $api_url = "https://api.kvk.nl/";
    protected $api_debug;
    protected $api_key;
    protected $disable_ssl_check;

    /** @var string $cache_prefix Cache key */
    protected $cache_prefix = 'kvk_service:kvk-number:';

    /** @var int $cache_time Cache time in minutes */
    protected $cache_time = 10;

    /**
     * KvkApi constructor.
     * @param bool $api_debug
     * @param string|null $api_key
     * @param bool $disable_ssl_check
     */
    public function __construct(
        bool $api_debug,
        string $api_key = null,
        bool $disable_ssl_check = false
    ) {
        $this->api_debug = $api_debug;
        $this->api_key = $api_key;
        $this->disable_ssl_check = $disable_ssl_check;
    }

    /**
     * @param string $kvk_number
     * @return string
     */
    public function getApiUrl(
        string $kvk_number
    ): string {
        return sprintf(
            "%sapi/v2/%s/companies?q=%s&user_key=%s",
            $this->api_url,
            $this->api_debug ? 'testprofile' : 'profile',
            $kvk_number,
            $this->api_key
        );
    }

    /**
     * @param string $kvk_number
     * @return bool|mixed
     */
    public function kvkNumberData(
        string $kvk_number
    ) {
        try {
            $response = json_decode($this->makeApiCall($kvk_number), false);

            if (is_object($response) && (count($response->data->items) > 0)) {
                return $response;
            }
        } catch (\Exception $e) {
            if ($logger = logger()) {
                $logger->error($e->getMessage());
            }
        }

        return false;
    }

    /**
     * @param string $kvk_number
     * @return mixed
     * @throws \Exception
     */
    private function makeApiCall(
        string $kvk_number
    ) {
        $cacheKey = $this->cache_prefix . $kvk_number;
        $cacheDuration = $this->cache_time * 60;

        return cache()->remember($cacheKey, $cacheDuration, function() use ($kvk_number) {
            $arrContextOptions = [
                "ssl" => [
                    "verify_peer" => !$this->disable_ssl_check,
                    "verify_peer_name" => !$this->disable_ssl_check,
                ],
            ];

            return file_get_contents($this->getApiUrl(
                $kvk_number
            ), false, stream_context_create($arrContextOptions));
        });
    }

    /**
     * @param string $kvk_number
     * @return \Illuminate\Support\Collection
     */
    public function getOffices(
        string $kvk_number
    ): Collection {
        $kvkData = $this->kvkNumberData($kvk_number);
        $addresses = $kvkData->data->items[0]->addresses;

        return collect($addresses)->map(function($address) {
            return [
                'original' => $address,
                'address' => sprintf(
                    "%s %s%s, %s, %s",
                    $address->street,
                    $address->houseNumber,
                    $address->houseNumberAddition,
                    $address->postalCode,
                    $address->city
                ),
                'lat' => $address->gpsLatitude,
                'lon' => $address->gpsLongitude,
            ];
        });
    }
}
