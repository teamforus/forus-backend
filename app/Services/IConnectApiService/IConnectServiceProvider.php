<?php

namespace App\Services\IConnectApiService;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class IConnectServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void
    {
        if (Config::get('forus.person_bsn.test_response', false)) {
            Http::fake([
                Str::finish(IConnect::URL_SANDBOX, '/') . '*' => function (Request $request) {
                    $url = parse_url($request->url());
                    $segments = explode('/', trim($url['path'], '/'));
                    $bsn = last($segments);

                    return Config::get("forus.person_bsn.test_response_data.$bsn")
                        ? Http::response(Config::get("forus.person_bsn.test_response_data.$bsn", []))
                        : Http::response(null, 404);
                },
            ]);
        }
    }
}
