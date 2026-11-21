<?php

namespace App\Services\MicrosoftMailService;

use App\Services\MicrosoftMailService\Transports\MicrosoftGraphTransport;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;

class MicrosoftMailServiceProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function boot(): void
    {
        Mail::extend('microsoft-graph', function (array $config): MicrosoftGraphTransport {
            return new MicrosoftGraphTransport(
                tenantId: $config['tenant_id'],
                clientId: $config['client_id'],
                clientSecret: $config['client_secret'],
                fromEmail: $config['from_email'],
            );
        });
    }
}
