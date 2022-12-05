<?php

namespace Tests\Traits;

use App\Models\IdentityEmail;
use App\Models\Traits\HasDbTokens;
use Illuminate\Support\Facades\Config;

trait MakesTestIdentities
{
    use HasDbTokens;

    /**
     * @param string $prefix
     * @param string|null $domain
     * @return string
     */
    protected function makeUniqueEmail(string $prefix = '', ?string $domain = null): string
    {
        $domain = $domain ?: Config::get('forus.tests.identity_domain', 'example.com');

        $token = self::makeUniqueTokenCallback(fn($token) => IdentityEmail::where([
            'email' => "$prefix$token@$domain"
        ])->doesntExist(), 8);

        return "$prefix$token@$domain";
    }
}