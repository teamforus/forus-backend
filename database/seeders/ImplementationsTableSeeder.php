<?php

namespace Database\Seeders;

use App\Models\Implementation;
use App\Services\MediaService\Models\Media;

class ImplementationsTableSeeder extends DatabaseSeeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     * @throws \Throwable
     */
    public function run(): void
    {
        if (Implementation::where('key', 'general')->exists()) {
            return;
        }

        $generalImplementation = Implementation::create([
            'key'           => 'general',
            'name'          => 'General',
            'title'         => 'General',
            'description'   => '',

            'email_color'       => config('forus.mail_styles.color_primary') ?: '#315EFD',
            'email_signature'   => null,

            'url_webshop'   => config('forus.front_ends.webshop', ''),
            'url_sponsor'   => config('forus.front_ends.panel-sponsor', ''),
            'url_provider'  => config('forus.front_ends.panel-provider', ''),
            'url_validator' => config('forus.front_ends.panel-validator', ''),
            'url_website'   => config('forus.front_ends.website-default', ''),
            'url_app'       => config('forus.front_ends.landing-app', ''),
            'lon'           => config('forus.front_ends.map.lon', ''),
            'lat'           => config('forus.front_ends.map.lat', ''),
        ]);

        $emailLogoPath = resource_path('/mail_templates/assets/general/auth_icon.jpg');

        if (file_exists($emailLogoPath)) {
            $generalImplementation->attachMediaByUid($this->makeImplementationEmailLogoMedia($emailLogoPath)->uid);
        }
    }

    /**
     * @throws \Throwable
     */
    protected function makeImplementationEmailLogoMedia(string $logoPath): Media
    {
        return resolve('media')->uploadSingle(
            $logoPath,
            'auth_icon.jpg',
            'email_logo',
            ['thumbnail', 'large']
        );
    }
}