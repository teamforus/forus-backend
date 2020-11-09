<?php

use App\Services\BunqService\Models\BunqIdealIssuer;

class BunqIdealIssuersTableSeeder extends DatabaseSeeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $issuers = [[
            "name" => "ABN Amro",
            "bic" => "ABNANL2A",
        ], [
            "name" => "ASN Bank",
            "bic" => "ASNBNL21"
        ], [
            "name" => "Handelsbanken",
            "bic" => "HANDNL2A"
        ], [
            "name" => "ING",
            "bic" => "INGBNL2A"
        ], [
            "name" => "Knab",
            "bic" => "KNABNL2H"
        ], [
            "name" => "Moneyou",
            "bic" => "MOYONL21"
        ], [
            "name" => "Rabobank",
            "bic" => "RABONL2U"
        ], [
            "name" => "RegioBank",
            "bic" => "RBRBNL21"
        ], [
            "name" => "SNS Bank",
            "bic" => "SNSBNL2A"
        ], [
            "name" => "Triodos Bank",
            "bic" => "TRIONL2U"
        ], [
            "name" => "Van Lanschot Bankiers",
            "bic" => "FVLBNL22"
        ]];

        foreach ($issuers as $issuer) {
            BunqIdealIssuer::query()->create(array_merge($issuer, [
                'sandbox' => 0
            ]));
        }

        $issuers = [[
            "name" => "ABN AMRO",
            "bic" => "ABNANL2A",
        ]];

        foreach ($issuers as $issuer) {
            BunqIdealIssuer::query()->create(array_merge($issuer, [
                'sandbox' => 1
            ]));
        }
    }
}
