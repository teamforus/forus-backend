<?php

namespace Database\Seeders;

use App\Models\Source;

class SourceTableSeeder extends DatabaseSeeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        Source::create([
            'key' => 'app.me_app',
            'url' => null,
        ]);

        Source::create([
            'key' => 'shop.test_shop',
            'url' => 'http://localhost:8000',
        ]);
    }
}
