<?php

namespace Tests\Traits;

use App\Models\IdentityEmail;
use App\Models\Record;
use App\Models\RecordType;
use App\Models\Traits\HasDbTokens;
use Illuminate\Support\Facades\Config;
use Throwable;

trait MakesTestIdentities
{
    use HasDbTokens;

    /**
     * @param bool $leadingZero
     * @throws Throwable
     * @return string
     */
    protected function makeUniqueBsn(bool $leadingZero = false): string
    {
        do {
            $bsn = $this->randomFakeBsn();
        } while (($leadingZero && !str_starts_with($bsn, '0')) || Record::query()
            ->where('value', $bsn)->whereRelation('record_type', 'key', RecordType::KEY_BSN)->exists());

        return $bsn;
    }

    /**
     * @param string $prefix
     * @param string|null $domain
     * @return string
     */
    protected function makeUniqueEmail(string $prefix = '', ?string $domain = null): string
    {
        $domain = $domain ?: Config::get('tests.identity_domain', 'example.com');

        $token = self::makeUniqueTokenCallback(fn ($token) => IdentityEmail::where([
            'email' => "$prefix$token@$domain",
        ])->doesntExist(), 8);

        return "$prefix$token@$domain";
    }
}
