<?php

namespace App\Services\KvkApiService;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;

/**
 * Class KvkApi
 * @package App\Services\KvkApiService
 */
class KvkApi
{
    protected string $api_url = "https://api.kvk.nl/";
    protected bool $api_debug;
    protected ?string $api_key;
    protected string $api_cert_path;

    /** @var string $cache_prefix Cache key */
    protected string $cache_prefix = 'kvk_service:kvk-number:';

    /** @var int $cache_time Cache time in minutes */
    protected int $cache_time = 10;

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
     * @param bool $detailed
     * @return string
     */
    public function getApiUrl(string $kvk_number, bool $detailed): string
    {
        return $detailed
            ? "{$this->api_url}api/v1/basisprofielen/$kvk_number/hoofdvestiging"
            : "{$this->api_url}api/v1/basisprofielen/$kvk_number";
    }

    /**
     * @param string $kvk_number
     * @param bool $detailed
     * @return bool|mixed
     */
    public function kvkNumberData(string $kvk_number, bool $detailed = false): ?object
    {
        try {
            $response = json_decode($this->makeApiCall($kvk_number, $detailed), false);
            return is_object($response) ? $response : null;
        } catch (\Throwable $e) {
            if ($logger = logger()) {
                $logger->error($e->getMessage());
            }
        }

        return null;
    }

    /**
     * @param string $kvk_number
     * @param bool $detailed
     * @return string|null
     */
    private function makeApiCall(string $kvk_number, bool $detailed): ?string
    {
        $cacheKey = $this->cache_prefix . $kvk_number . ($detailed ? '.detailed' : '');
        $cacheDuration = $this->cache_time * 60;

        return Cache::remember($cacheKey, $cacheDuration, function() use ($kvk_number, $detailed) {
            return (new Client())->get($this->getApiUrl($kvk_number, $detailed), [
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
        $kvkData = $this->kvkNumberData($kvk_number, true);
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
