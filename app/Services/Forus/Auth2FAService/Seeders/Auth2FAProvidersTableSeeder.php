<?php

namespace App\Services\Forus\Auth2FAService\Seeders;

use App\Helpers\Arr;
use App\Services\Forus\Auth2FAService\Models\Auth2FAProvider;
use Illuminate\Database\Seeder;

class Auth2FAProvidersTableSeeder extends Seeder
{
    /**
     * @var array|array[]
     */
    protected array $providers = [[
        'key' => 'phone',
        'name' => 'Phone number',
        'type' => 'phone',
    ], [
        'key' => 'authenticator_google',
        'name' => 'Google authenticator',
        'type' => 'authenticator',
        'url_ios' => 'https://apps.apple.com/us/app/google-authenticator/id388497605',
        'url_android' => 'https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2',
    ], [
        'key' => 'authenticator_microsoft',
        'name' => 'Microsoft Authenticator',
        'type' => 'authenticator',
        'url_ios' => 'https://go.microsoft.com/fwlink/p/?LinkID=2168643',
        'url_android' => 'https://go.microsoft.com/fwlink/p/?LinkID=2168850',
    ], [
        'key' => 'authenticator_lastpass',
        'name' => 'LastPass Authenticator',
        'type' => 'authenticator',
        'url_ios' => 'https://itunes.apple.com/us/app/lastpass-for-premium-customers/id324613447?mt=8&uo=4',
        'url_android' => 'https://play.google.com/store/apps/details?id=com.lastpass.lpandroid',
    ], [
        'key' => 'authenticator_1password',
        'name' => '1Password authenticator',
        'type' => 'authenticator',
        'url_ios' => 'https://apps.apple.com/app/id1511601750?mt=8',
        'url_android' => 'https://play.google.com/store/apps/details?id=com.onepassword.android',
    ]];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        foreach ($this->providers as $provider) {
            if (Auth2FAProvider::where(Arr::only($provider, ['key', 'type']))->doesntExist()) {
                Auth2FAProvider::forceCreate($provider);
            }
        }
    }
}
