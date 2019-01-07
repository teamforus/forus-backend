<?php

namespace App\Services\KvkApiService;

/**
 * Class KvkApi
 * @package App\Services\KvkApiService
 */
class KvkApi
{
    protected $api_url = "https://api.kvk.nl/";
    protected $api_debug = null;
    protected $api_key = null;

    /** @var string $cache_prefix Cache key */
    protected $cache_prefix = 'kvk_service:kvk-number:';
    /** @var int $cache_time Cache time in minutes */
    protected $cache_time = 10;

    /**
     * KvkApi constructor.
     * @param bool $api_debug
     * @param string|null $api_key
     */
    function __construct(
        bool $api_debug,
        string $api_key = null
    ) {
        $this->api_debug = $api_debug;
        $this->api_key = $api_key;
    }

    /**
     * @param string $kvk_number
     * @return string
     */
    public function getApiUrl(
        string $kvk_number
    ) {
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
            $response = json_decode($this->makeApiCall(
                $this->cache_prefix . $kvk_number,
                $this->cache_time,
                $kvk_number
            ));

            if (is_object($response)) {
                return $response;
            }
        } catch (\Exception $e) {}

        return false;
    }

    /**
     * @param string $cacheKey
     * @param int $cacheTime
     * @param string $kvk_number
     * @return mixed
     * @throws \Exception
     */
    private function makeApiCall(
        string $cacheKey,
        int $cacheTime,
        string $kvk_number
    ) {
        return cache()->remember($cacheKey, $cacheTime, function() use (
            $kvk_number
        ) {
            return file_get_contents($this->getApiUrl($kvk_number));
        });
    }

    /**
     * @param string $kvk_number
     * @return \Illuminate\Support\Collection
     */
    public function getOffices(
        string $kvk_number
    ) {
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