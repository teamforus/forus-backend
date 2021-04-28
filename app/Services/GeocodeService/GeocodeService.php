<?php

namespace App\Services\GeocodeService;
use GuzzleHttp\Client;
use Illuminate\Support\Arr;

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
    public function getApiUrl($address): string {
        return sprintf(
            "%sgeocode/json?address=%s&key=%s", $this->api_url,
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
    public function getLocation($address)
    {
        try {
            $client = new Client();
            $res = $client->get($this->getApiUrl($address));

            $coordinates = $res->getBody();
            $coordinates = json_decode($coordinates, true);

            $result = $coordinates['results'][0];
            $postalData = $this->addressComponentsToPostcode($result['address_components']);
            $coordinatesData = Arr::only($result['geometry']['location'], ['lng', 'lat']);

            return array_merge($coordinatesData, $postalData);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param array $address_components
     * @return array
     */
    protected function addressComponentsToPostcode(array $address_components): array
    {
        $postalCode = array_first(array_filter($address_components, function($component) {
            return in_array('postal_code', $component['types']);
        }))['long_name'] ?? null;

        $postalCodeData = $postalCode ? explode(' ', $postalCode) : [];

        return [
            'postcode' => $postalCode ?? null,
            'postcode_number' => $postalCodeData[0] ?? null,
            'postcode_addition' => $postalCodeData[1] ?? null,
        ];
    }
}