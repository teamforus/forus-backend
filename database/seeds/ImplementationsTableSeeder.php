<?php

use App\Models\Implementation;

class ImplementationsTableSeeder extends DatabaseSeeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        if (Implementation::where('key', 'general')->exists()) {
            return;
        }

        Implementation::create([
            'key'           => 'general',
            'name'          => 'General',
            'title'         => 'General',
            'description'   => '',

            'url_webshop'   => config('forus.front_ends.webshop', ''),
            'url_sponsor'   => config('forus.front_ends.panel-sponsor', ''),
            'url_provider'  => config('forus.front_ends.panel-provider', ''),
            'url_validator' => config('forus.front_ends.panel-validator', ''),
            'url_website'   => config('forus.front_ends.website-default', ''),
            'url_app'       => config('forus.front_ends.landing-app', ''),
            'lon'           => config('forus.front_ends.map.lon', ''),
            'lat'           => config('forus.front_ends.map.lat', ''),
        ]);
    }
}
