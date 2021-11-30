<?php

namespace App\Services\KvkApiService;

use GuzzleHttp\Client;

/**
 * Class KvkApi
 * @package App\Services\KvkApiService
 */
class KvkApi
{
    protected $api_url = "https://api.kvk.nl/";
    protected $api_debug;
    protected $api_key;
    protected $api_cert_path;

    /** @var string $cache_prefix Cache key */
    protected $cache_prefix = 'kvk_service:kvk-number:';

    /** @var int $cache_time Cache time in minutes */
    protected $cache_time = 10;

    /**
     * KvkApi constructor.
     * @param bool $api_debug
     * @param string|null $api_key
     */
    public function __construct(bool $api_debug, string $api_key = null)
    {
        $this->api_key = $api_key;
        $this->api_debug = $api_debug;
        $this->api_url = $this->api_url . ($api_debug ? 'test/' : '');
        $this->api_cert_path = storage_path('/kvk-api/api_kvk_nl.crt');
    }

    /**
     * @param string $kvk_number
     * @return string
     */
    public function getApiUrl(string $kvk_number): string
    {
        return "{$this->api_url}api/v1/basisprofielen/$kvk_number/hoofdvestiging";
    }

    /**
     * @param string $kvk_number
     * @return bool|mixed
     */
    public function kvkNumberData(string $kvk_number): ?object
    {
        try {
            $response = json_decode($this->makeApiCall($kvk_number), false);
            return is_object($response) ? $response : null;
        } catch (\Exception $e) {
            if ($logger = logger()) {
                $logger->error($e->getMessage());
            }
        }

        return null;
    }

    /**
     * @param string $kvk_number
     * @return string|null
     * @throws \Exception
     */
    private function makeApiCall(string $kvk_number): ?string
    {
        $cacheKey = $this->cache_prefix . $kvk_number;
        $cacheDuration = $this->cache_time * 60;

        return cache()->remember($cacheKey, $cacheDuration, function() use ($kvk_number) {
            return (new Client())->get($this->getApiUrl($kvk_number), [
                'verify' => $this->api_cert_path,
                'headers' => [
                    'apikey' => $this->api_key
                ],
            ])->getBody()->getContents();
        });
    }

    /**
     * @param string $kvk_number
     * @return array
     */
    public function getOffices(string $kvk_number): array
    {
        $kvkData = $this->kvkNumberData($kvk_number);
        $addresses = $kvkData->adressen ?? [];
        $geocodeService = resolve('geocode_api');

        return array_map(function($addressItem) use ($geocodeService) {
            $address = sprintf(
                "%s %s, %s, %s",
                $addressItem->straatnaam,
                $addressItem->huisnummer,
                $addressItem->postcode,
                $addressItem->plaats
            );

            $arr = compact('address');
            $location = $geocodeService->getLocation($address);

            if (is_array($location)) {
                $arr['lon'] = $location['lng'] ?? null;
                $arr['lat'] = $location['lat'] ?? null;
            }

            return $arr;
        }, $addresses);
    }
}
