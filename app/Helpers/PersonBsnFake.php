<?php

namespace App\Helpers;

use Illuminate\Support\Carbon;

class PersonBsnFake
{
    /**
     * @param int $age
     * @return array
     */
    public static function makeBirthData(int $age): array
    {
        $date = Carbon::today()->copy()->subYears($age);

        return [
            'leeftijd' => $age,
            'geboorte' => [
                'datum' => [
                    'datum' => $date->format('Y-m-d'),
                    'jaar' => (int) $date->format('Y'),
                    'maand' => (int) $date->format('m'),
                    'dag' => (int) $date->format('d'),
                ],
            ],
        ];
    }
}
