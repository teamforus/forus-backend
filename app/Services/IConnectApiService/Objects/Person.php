<?php

namespace App\Services\IConnectApiService\Objects;

use Illuminate\Support\Arr;

/**
 * Class Person
 * @package App\Services\IConnectApiService\Responses
 */
class Person extends BasePerson
{
    /** @var array  */
    protected array $parents;

    /** @var array  */
    protected array $children;

    /**
     * @return string
     */
    public function getNationality(): string
    {
        return $this->data['nationaliteiten'][0]['nationaliteit']['omschrijving'] ?? '';
    }

    /**
     * @return int
     */
    public function getIndex(): int
    {
        return 1;
    }

    /**
     * @return bool
     */
    public function getIsDeceased(): bool
    {
        return (bool) ($this->data['overlijden']['indicatieOverleden'] ?? false);
    }

    /**
     * @return string|null
     */
    public function getDateDeceased(): ?string
    {
        return $this->data['overlijden']['datum']['datum'] ?? null;
    }

    /**
     * @return array
     */
    public function getResidence(): array
    {
        return [
            'street' => $this->data['verblijfplaats']['straat'] ?? '',
            'house_number' => $this->data['verblijfplaats']['huisnummer'] ?? '',
            'postcode' => $this->data['verblijfplaats']['postcode'] ?? '',
            'function_address' => $this->data['verblijfplaats']['functieAdres'] ?? '',
            'short_name' => $this->data['verblijfplaats']['korteNaam'] ?? '',
            'date_start_address_attitude' => $this->data['verblijfplaats']['datumAanvangAdreshouding']['datum'] ?? '',
            'date_entry_validity' => $this->data['verblijfplaats']['datumIngangGeldigheid']['datum'] ?? '',
            'date_registration_in_municipality' => $this->data['verblijfplaats']['datumInschrijvingInGemeente']['datum'] ?? '',
            'municipality_of_registration' => $this->data['verblijfplaats']['gemeenteVanInschrijving']['omschrijving'] ?? '',
            'address' => implode('', [
                $this->data['verblijfplaats']['adresregel1'] ?? '',
                $this->data['verblijfplaats']['adresregel2'] ?? '',
                $this->data['verblijfplaats']['adresregel3'] ?? '',
            ])
        ];
    }

    /**
     * @return string
     */
    public function getAddress(): string
    {
        $residence = $this->getResidence();

        return implode("\n", [
            implode(' ', array_filter([
                Arr::get($residence, 'street'),
                Arr::get($residence, 'house_number'),
            ])),
            Arr::get($residence, 'postcode'),
        ]);
    }

    /**
     * @return array|ParentPerson[]
     */
    public function getParents(): array
    {
        return array_map(function(array $item) {
            return new ParentPerson($item);
        }, $this->data['_embedded']['ouders'] ?? []);
    }

    /**
     * @return array|Child[]
     */
    public function getChildren(): array
    {
        return array_map(function(array $item) {
            return new Child($item);
        }, $this->data['_embedded']['kinderen'] ?? []);
    }

    /**
     * @return array|Partner[]
     */
    public function getPartners(): array
    {
        return array_map(function(array $item) {
            return new Partner($item);
        }, $this->data['_embedded']['partners'] ?? []);
    }

    /**
     * @return array
     */
    public function getCustomDataArray(): array
    {
        return [
            'nationality' => $this->getNationality(),
            'residence' => $this->getResidence(),
            'address' => $this->getAddress(),
            'decreased' => $this->getIsDeceased() ? 'Ja' : 'Nee',
            'decreased_date' => $this->getDateDeceased(),
        ];
    }

    /**
     * @param string $scope
     * @return Child[]|ParentPerson[]|Partner[]|array
     */
    public function geRelated(string $scope): array
    {
        switch ($scope) {
            case 'partners': return $this->getPartners();
            case 'children': return $this->getChildren();
            case 'parents': return $this->getParents();
            default: return [];
        }
    }

    /**
     * @param string $scope
     * @param int $scopeId
     * @return BasePerson|null
     */
    public function getRelatedByIndex(string $scope, int $scopeId): ?BasePerson
    {
        $persons = $this->geRelated($scope);

        return Arr::first($persons, static function(BasePerson $child) use ($scopeId) {
            return $child->getIndex() === $scopeId;
        });
    }
}
