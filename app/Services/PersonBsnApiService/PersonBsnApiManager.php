<?php

namespace App\Services\PersonBsnApiService;

use App\Models\Organization;
use App\Services\IConnectApiService\IConnect;
use App\Services\PersonBsnApiService\Interfaces\PersonBsnApiInterface;
use InvalidArgumentException;

class PersonBsnApiManager
{
    protected Organization $organization;

    /**
     * @param Organization $organization
     */
    public function __construct(Organization $organization)
    {
        $this->organization = $organization;
    }

    /**
     * @param string|null $driver
     * @return PersonBsnApiInterface
     */
    public function driver(?string $driver = null): PersonBsnApiInterface
    {
        $driver = $driver ?? config('forus.person_bsn.default');

        $class = match ($driver) {
            'iconnect' => IConnect::class,
            default => throw new InvalidArgumentException("Person BSN driver [$driver] not supported.")
        };

        return new $class($this->organization);
    }

    /**
     * @param string|null $driver
     * @return bool
     */
    public function hasConnection(?string $driver = null): bool
    {
        $driver = $driver ?? config('forus.person_bsn.default');

        return match ($driver) {
            'iconnect' => $this->organization->hasIConnectApiOin(),
            default => throw new InvalidArgumentException("Person BSN driver [$driver] not supported.")
        };
    }
}
