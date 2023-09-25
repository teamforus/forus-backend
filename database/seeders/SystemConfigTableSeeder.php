<?php

namespace Database\Seeders;

use App\Models\SystemConfig;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;

class SystemConfigTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $firestoreKey = Config::get('forus.seeders.system_configs.firestorm_key');
        $firestoreContext = storage_path('firestore/firestore-context.json');

        SystemConfig::create([
            'key' => 'firestore_context',
            'value' => file_exists($firestoreContext) ? file_get_contents($firestoreContext) : null,
        ]);

        SystemConfig::create([
            'key' => 'firestore_key',
            'value' => $firestoreKey,
        ]);
    }
}
