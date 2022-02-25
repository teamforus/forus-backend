<?php

namespace App\Services\BNGService;

use Illuminate\Support\ServiceProvider;

class BNGServiceProvider extends ServiceProvider
{
    /**
     * @return void
     * @noinspection PhpUnused
     */
    public function boot(): void
    {
        $this->app->singleton('bng_service', function () {
            $authRedirectUrl = url(rtrim(config('forus.bng.auth_redirect_url'), '/'));
            $psuApi = env('BNG_PSU_IP_ADDRESS', 'auto');

            return new BNGService(env('BNG_ENV', BNGService::ENV_SANDBOX), [
                'keyId' => str_replace(["\n", "\r"], "", env('BNG_KEY_ID', '')),
                'clientId' => env('BNG_CLIENT_ID', 'PSDNL-AUT-SANDBOX'),
                'signatureCertificatePath' => storage_path(env('BNG_SIGNATURE_CERT_PATH', '')),
                'signatureCertificateKeyPath' => storage_path(env('BNG_SIGNATURE_KEY_PATH', '')),
                'cert' => storage_path(env('BNG_TLS_CERT_PATH', '')),
                'ssl_key' => storage_path(env('BNG_TLS_KEY_PATH', '')),
                'psuIpAddress' => $psuApi == 'auto' ? request()->server('SERVER_ADDR') : $psuApi,
                'authRedirectUrl' => $authRedirectUrl,
            ]);
        });
    }
}