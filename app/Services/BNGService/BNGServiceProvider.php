<?php

namespace App\Services\BNGService;

use App\Services\BankService\Models\Bank;
use Illuminate\Support\Arr;
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
            $bngBank = Bank::where('key', 'bng')->first();

            if ($bngBank) {
                return $this->getBngServiceInstance($bngBank);
            }

            throw new \Exception("BNG Bank not found.");
        });
    }

    /**
     * @param Bank $bngBank
     * @return BNGService
     */
    protected function getBngServiceInstance(Bank $bngBank): BNGService
    {
        $psuIp = config('forus.bng.psu_ip', 'auto');
        $env = $bngBank->data['env'] ?? 'sandbox';

        return new BNGService($env, array_merge(Arr::only($bngBank->data, [
            'keyId', 'clientId',
            'signatureCertificate', 'signatureCertificateKey',
            'tlsCertificate', 'tlsCertificateKey',
        ]), [
            'psuIpAddress' => $psuIp == 'auto' ? request()->server('SERVER_ADDR') : $psuIp,
            'authRedirectUrl' => url(rtrim(config('forus.bng.auth_redirect_url'), '/')),
        ]));
    }
}