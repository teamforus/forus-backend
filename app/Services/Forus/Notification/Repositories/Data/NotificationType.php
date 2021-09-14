<?php

namespace App\Services\Forus\Notification\Repositories\Data;

class NotificationType
{
    protected $key;
    protected $scope;
    protected $channels;

    /**
     * @param string $key
     * @param string|null $scope
     * @param array $channels
     */
    public function __construct(string $key, ?string $scope, array $channels)
    {
        $this->key = $key;
        $this->scope = $scope;
        $this->channels = $channels;
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @return string|null
     */
    public function getScope(): ?string
    {
        return $this->scope;
    }

    /**
     * @return array
     */
    public function getChannels(): array
    {
        return $this->channels;
    }
}