<?php

namespace App\Services\PersonBsnApiService;

use App\Models\Organization;
use App\Services\IConnectApiService\IConnect;
use App\Services\PersonBsnApiService\Interfaces\PersonBsnApiInterface;
use Illuminate\Support\Facades\Config;
use InvalidArgumentException;

class PersonBsnApiManager
{
    /**
     * @param Organization $organization
     */
    protected function __construct(protected Organization $organization)
    {
    }

    /**
     * @param Organization $organization
     * @return $this
     */
    public static function make(Organization $organization): PersonBsnApiManager
    {
        return new static($organization);
    }

    /**
     * @param string|null $driver
     * @return PersonBsnApiInterface
     */
    public function driver(?string $driver = null): PersonBsnApiInterface
    {
        $driver = $driver ?? $this->getDefaultDriver();

        return match ($driver) {
            'iconnect' => new PersonBsnIConnectAdapter(new IConnect($this->organizationToConfigs($this->organization))),
            default => throw new InvalidArgumentException("Person BSN driver [$driver] not supported.")
        };
    }

    /**
     * @param string|null $driver
     * @return bool
     */
    public function hasConnection(?string $driver = null): bool
    {
        $driver = $driver ?? $this->getDefaultDriver();

        return match ($driver) {
            'iconnect' => $this->organization->hasIConnectApiOin(),
            default => throw new InvalidArgumentException("Person BSN driver [$driver] not supported.")
        };
    }

    /**
     * @return string
     */
    protected function getDefaultDriver(): string
    {
        return Config::get('forus.person_bsn.default');
    }

    /**
     * @param Organization $organization
     * @return array
     */
    private function organizationToConfigs(Organization $organization): array
    {
        return [
            'env' => $organization->iconnect_env,
            'api_oin' => $organization->iconnect_api_oin,
            'cert' => $organization->iconnect_cert,
            'cert_pass' => $organization->iconnect_cert_pass,
            'cert_trust' => $organization->iconnect_cert_trust,
            'key' => $organization->iconnect_key,
            'key_pass' => $organization->iconnect_key_pass,
            'base_url' => $organization->iconnect_base_url,
            'target_binding' => $organization->iconnect_target_binding,
        ];
    }
}
