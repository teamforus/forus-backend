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

    /** @var string $cache_prefix Cache key */
    protected $cache_prefix = 'kvk_service:kvk-number:';

    /** @var int $cache_time Cache time in minutes */
    protected $cache_time = 10;

    /**
     * KvkApi constructor.
     * @param bool $api_debug
     * @param string|null $api_key
     */
    public function __construct(
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
    ): string {
        // https://api.kvk.nl/api/v2/testprofile/companies?q=rminds&user_key=l7xx2507da46416f4404a9398f7d364e7dda
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
            ), true);

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
        return cache()->remember($cacheKey, $cacheTime * 60, function() use (
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
