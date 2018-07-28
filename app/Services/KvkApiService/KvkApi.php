<?php

namespace App\Services\KvkApiService;

class KvkApi
{   
    protected $api_url = "https://api.kvk.nl/";
    protected $api_key = null;

    protected $fake_response = false;

    function __construct($api_key)
    {
        $this->setApi($api_key);

        if (env('KVK_FAKE', false))
            $this->fake_response = '{
            "apiVersion": "2.0",
            "meta": {},
            "data": {
                "itemsPerPage": 1,
                "startPage": 1,
                "totalItems": 1,
                "items": [{
                    "kvkNumber": "69097488",
                    "branchNumber": "000029030226",
                    "rsin": "857732894",
                    "tradeNames": {
                        "businessName": "Rocket Minds",
                        "shortBusinessName": "Rocket Minds",
                        "currentTradeNames": ["Rocket Minds", "Weget Enterprise", "OnePlan"],
                        "currentNames": ["Rocket Minds"],
                        "formerNames": ["Weget Enterprise"]
                    },
                    "legalForm": "Vennootschap onder firma",
                    "businessActivities": [{
                        "sbiCode": "6201",
                        "sbiCodeDescription": "Ontwikkelen, produceren en uitgeven van software",
                        "isMainSbi": true
                    }],
                    "hasEntryInBusinessRegister": true,
                    "hasCommercialActivities": true,
                    "hasNonMailingIndication": true,
                    "isLegalPerson": false,
                    "isBranch": true,
                    "isMainBranch": true,
                    "employees": 2,
                    "foundationDate": "20140114",
                    "registrationDate": "20170703",
                    "addresses": [{
                        "type": "vestigingsadres",
                        "bagId": "0014010011021267",
                        "street": "Ulgersmaweg",
                        "houseNumber": "35",
                        "houseNumberAddition": "",
                        "postalCode": "9731BK",
                        "city": "Groningen",
                        "country": "Nederland",
                        "gpsLatitude": 53.23383975021,
                        "gpsLongitude": 6.5872591201383,
                        "rijksdriehoekX": 235129.241,
                        "rijksdriehoekY": 583694.928,
                        "rijksdriehoekZ": 0
                    }],
                    "websites": ["www.rocketminds.nl"]
                }]
            }
        }';
    }

    public function setApi($api_key)
    {
        $this->api_key = $api_key;
    }

    public function kvkNumberData($kvk_number)
    {
        $data = false;
        
        try {
            if (!$this->fake_response)
                $response = json_decode(file_get_contents(
                    $this->api_url . "api/v2/profile/companies?q=" . 
                    $kvk_number . "&user_key=" . $this->api_key));
            else
                $response = json_decode($this->fake_response);

            if (is_object($response))
                $data = $response;
        } catch (\Exception $e) {}

        return $data;
    }
}