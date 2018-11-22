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

    protected $cache_prefix = 'kvk_service:kvk-number:';
    protected $cache_time = 10;

    protected $test_response = false;

    function __construct(
        $api_debug,
        $api_key
    ) {
        $this->api_debug = $api_debug;
        $this->api_key = $api_key;
    }

    public function getApiUrl($kvk_number) {
        return sprintf(
            "%sapi/v2/profile/companies?q=%s&user_key=%s",
            $this->api_url,
            $kvk_number,
            $this->api_key
        );
    }

    public function kvkNumberData($kvk_number)
    {
        try {
            if ($this->api_debug) {
                $response = json_decode(file_get_contents(
                    __DIR__ . '/Debug/test.json'
                ));
            } else {
                $self = $this;

                $response =  cache()->remember(
                    $this->cache_prefix . $kvk_number,
                    $this->cache_time,
                    function() use ($kvk_number, $self) {
                    return json_decode(file_get_contents(
                        $this->getApiUrl($kvk_number)
                    ));
                });
            }

            if (is_object($response)) {
                return $response;
            }
        } catch (\Exception $e) {}

        return false;
    }

    /**
     * @param $kvk_number
     * @return \Illuminate\Support\Collection
     */
    public function getOffices($kvk_number) {
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