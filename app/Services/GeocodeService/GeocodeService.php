<?php

namespace App\Services\GeocodeService;
use GuzzleHttp\Client;

/**
 * Class GeocodeService
 * @package App\Services\GeocodeService
 */
class GeocodeService
{   
    protected $api_url = "https://maps.googleapis.com/maps/api/";
    protected $api_key = null;

    function __construct(
        $api_key
    ) {
        $this->api_key = $api_key;
    }

    /**
     * Compose api endpoint address
     *
     * @param $address
     * @return string
     */
    public function getApiUrl($address) {
        return sprintf(
            "%sgeocode/json?address=%s&key=%s",
            $this->api_url,
            $address,
            $this->api_key
        );
    }

    /**
     * Get address geocode
     *
     * @param $address
     * @return array|bool
     */
    public function getCoordinates($address)
    {
        try {
            $client = new Client();
            $res = $client->get($this->getApiUrl($address));

            $coordinates = $res->getBody();
            $coordinates = json_decode($coordinates);

            $location = $coordinates->results[0]->geometry->location;

            return [
                'lat' => $location->lat,
                'lon' => $location->lng,
            ];
        } catch (\Exception $e) {
            return false;
        }
    }
}