<?php

namespace App\Services\PersonBsnApiService\Interfaces;

use App\Services\IConnectApiService\Responses\ResponseData;

interface PersonInterface
{
    /**
     * @return array
     */
    public function getData(): array;

    /**
     * @param string $scope
     * @param int $scopeId
     * @return PersonInterface|null
     */
    public function getRelatedByIndex(string $scope, int $scopeId): ?PersonInterface;

    /**
     * @param string $scope
     * @return PersonInterface[]
     */
    public function getRelated(string $scope): array;

    /**
     * @return string|null
     */
    public function getBSN(): ?string;

    /**
     * @return string|null
     */
    public function getName(): ?string;

    /**
     * @return int
     */
    public function getIndex(): int;

    /**
     * @return string
     */
    public function getAddress(): string;

    /**
     * @return array
     */
    public function toArray(): array;

    /**
     * @return ResponseData|null
     */
    public function response(): ?ResponseData;
}
