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

    /**
     * Get postal code
     *
     * @param $address
     * @return string|bool
     */
    public function getPostalCode($address)
    {
        try {
            $client = new Client();
            $res = $client->get($this->getApiUrl($address));

            $coordinates = $res->getBody();
            $coordinates = json_decode($coordinates);

            $address_components = $coordinates->results[0]->address_components;

            $postal_code = null;
            foreach ($address_components as $address_component) {
                if (in_array('postal_code', $address_component->types)) {
                    $postal_code = $address_component->long_name;
                }
            }

            return $postal_code;
        } catch (\Exception $e) {
            return false;
        }
    }
}