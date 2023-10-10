<?php

namespace Database\Seeders;

use App\Models\Implementation;
use App\Services\MediaService\Models\Media;
use Illuminate\Support\Facades\Config;

class ImplementationsTableSeeder extends DatabaseSeeder
{
    /**
     * @var string
     */
    protected string $samlContextPath = 'seeders/db/digid_saml/digid-saml-data.json';

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

        $generalImplementation = Implementation::forceCreate([
            'key'                   => 'general',
            'name'                  => 'General',
            'title'                 => 'General',
            'description'           => '',

            'email_color'           => Config::get('forus.mail_styles.color_primary') ?: '#315EFD',
            'email_signature'       => null,

            'url_webshop'           => Config::get('forus.front_ends.webshop', ''),
            'url_sponsor'           => Config::get('forus.front_ends.panel-sponsor', ''),
            'url_provider'          => Config::get('forus.front_ends.panel-provider', ''),
            'url_validator'         => Config::get('forus.front_ends.panel-validator', ''),
            'url_app'               => Config::get('forus.front_ends.landing-app', ''),
            'lon'                   => Config::get('forus.front_ends.map.lon', ''),
            'lat'                   => Config::get('forus.front_ends.map.lat', ''),
            'digid_connection_type' => 'saml',
            'digid_saml_context'    => $this->getDefaultSamlContext(),
        ]);

        $emailLogoPath = resource_path('/mail_templates/assets/general/auth_icon.jpg');

        if (file_exists($emailLogoPath)) {
            $generalImplementation->attachMediaByUid($this->makeImplementationEmailLogoMedia($emailLogoPath)->uid);
        }
    }

    /**
     * @return array|null
     */
    protected function getDefaultSamlContext(): ?array
    {
        if (file_exists(database_path($this->samlContextPath))) {
            return json_decode(file_get_contents(database_path($this->samlContextPath)), true);
        }

        return null;
    }

    /**
     * @throws \Throwable
     */
    protected function makeImplementationEmailLogoMedia(string $logoPath): Media
    {
        return resolve('media')->uploadSingle($logoPath, 'auth_icon.jpg', 'email_logo', [
            'thumbnail', 'large',
        ]);
    }
}
