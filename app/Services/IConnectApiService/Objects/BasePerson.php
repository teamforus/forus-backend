<?php

namespace App\Services\IConnectApiService\Objects;

use App\Services\IConnectApiService\Responses\ResponseData;
use Illuminate\Support\Arr;

/**
 * Class BasePerson
 * @package App\Services\IConnectApiService\Responses
 */
abstract class BasePerson
{
    /** @var array|null  */
    protected ?array $data = null;
    protected ?ResponseData $response = null;

    /**
     * @param ResponseData|array $response
     */
    public function __construct($response)
    {
        if (is_array($response)) {
            $this->data = $response;
        } else {
            $this->response = $response;
            $this->data = $response->getData();
        }
    }

    /**
     * @return ResponseData
     */
    public function response(): ?ResponseData
    {
        return $this->response;
    }

    /**
     * @return int
     */
    public function getIndex(): int
    {
        $array = explode("/", $this->data['_links']['self']['href'] ?? '');
        return (int) end($array);
    }

    public function getName(): string
    {
        return ($this->getFirstName() . ' ' . $this->getLastName()) ?: null;
    }

    /**
     * @return string|null
     */
    public function getFirstName(): ?string
    {
        return $this->data['naam']['voornamen'] ?? null;
    }

    /**
     * @return string|null
     */
    public function getLastName(): ?string
    {
        return $this->data['naam']['geslachtsnaam'] ?? null;
    }

    /**
     * @return string|null
     */
    public function getBSN(): ?string
    {
        return $this->data['burgerservicenummer'] ?? null;
    }

    /**
     * @return string|null
     */
    public function getGender(): ?string
    {
        return $this->data['geslachtsaanduiding'] ?? null;
    }

    /**
     * @return string|null
     */
    public function getAge(): ?string
    {
        return $this->data['leeftijd'] ?? null;
    }

    /**
     * @return string|null
     */
    public function getBirthdate(): ?string
    {
        return $this->data['geboorte']['datum']['datum'] ?? null;
    }

    /**
     * @return (mixed|null)[]
     *
     * @psalm-return array{country: mixed|null, place: mixed|null}
     */
    public function getBirthplace(): array
    {
        return [
            'country' => $this->data['geboorte']['land']['omschrijving'] ?? null,
            'place' => $this->data['geboorte']['plaats']['omschrijving'] ?? null,
        ];
    }

    /**
     * @return (mixed|null|string)[]
     *
     * @psalm-return array{name: mixed|null|string, first_name: mixed|null|string, last_name: mixed|null|string, birth_date: mixed|null|string, birth_place: mixed|string, gender: mixed|null|string, bsn: mixed|null|string, age: mixed|null|string,...}
     */
    public function toArray(): array
    {
        return array_merge([
            'name' => $this->getName(),
            'first_name' => $this->getFirstName(),
            'last_name' => $this->getLastName(),
            'birth_date' => $this->getBirthdate(),
            'birth_place' => implode(', ', Arr::only($this->getBirthplace(), ['place', 'country'])),
            'gender' => $this->getGender(),
            'bsn' => $this->getBSN(),
            'age' => $this->getAge(),
        ], $this->getCustomDataArray());
    }

    /**
     * @param string $scope
     *
     * @return array
     *
     * @psalm-return array<never, never>
     */
    public function geRelated(string $scope): array
    {
        return [];
    }

    /**
     * @return array
     */
    abstract public function getCustomDataArray(): array;
}
