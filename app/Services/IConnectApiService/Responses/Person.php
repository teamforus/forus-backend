<?php

namespace App\Services\IConnectApiService\Responses;

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

    /** @var array  */
    protected array $partners;

    /**
     * @return string
     */
    public function getNationality(): string
    {
        return $this->raw['nationaliteiten'][0]['nationaliteit']['omschrijving'] ?? '';
    }

    /**
     * @return bool
     */
    public function getIsDeceased(): bool
    {
        return (bool) ($this->raw['overlijden']['indicatieOverleden'] ?? false);
    }

    /**
     * @return string|null
     */
    public function getDateDeceased(): ?string
    {
        return $this->raw['overlijden']['datum']['datum'] ?? null;
    }

    /**
     * @return array
     */
    public function getResidence(): array
    {
        return [
            'street' => $this->raw['verblijfplaats']['straat'] ?? '',
            'house_number' => $this->raw['verblijfplaats']['huisnummer'] ?? '',
            'postcode' => $this->raw['verblijfplaats']['postcode'] ?? '',
            'function_address' => $this->raw['verblijfplaats']['functieAdres'] ?? '',
            'short_name' => $this->raw['verblijfplaats']['korteNaam'] ?? '',
            'date_start_address_attitude' => $this->raw['verblijfplaats']['datumAanvangAdreshouding']['datum'] ?? '',
            'date_entry_validity' => $this->raw['verblijfplaats']['datumIngangGeldigheid']['datum'] ?? '',
            'date_registration_in_municipality' => $this->raw['verblijfplaats']['datumInschrijvingInGemeente']['datum'] ?? '',
            'municipality_of_registration' => $this->raw['verblijfplaats']['gemeenteVanInschrijving']['omschrijving'] ?? '',
            'address' => implode('', [
                $this->raw['verblijfplaats']['adresregel1'] ?? '',
                $this->raw['verblijfplaats']['adresregel2'] ?? '',
                $this->raw['verblijfplaats']['adresregel3'] ?? '',
            ])
        ];
    }

    /**
     * @return array|ParentPerson[]
     */
    public function getParents(): array
    {
        return array_map(function(array $item) {
            return new ParentPerson($item);
        }, $this->raw['_embedded']['ouders'] ?? []);
    }

    /**
     * @return array|Child[]
     */
    public function getChildren(): array
    {
        return array_map(function(array $item) {
            return new Child($item);
        }, $this->raw['_embedded']['kinderen'] ?? []);
    }

    /**
     * @return array|Partner[]
     */
    public function getPartners(): array
    {
        return array_map(function(array $item) {
            return new Partner($item);
        }, $this->raw['_embedded']['partners'] ?? []);
    }

    /**
     * @return array
     */
    public function getCustomDataArray(): array
    {
        return [
            'nationality' => $this->getNationality(),
            'residence' => $this->getResidence(),
            'decreased' => $this->getIsDeceased(),
            'decreased_date' => $this->getDateDeceased(),
            'parents' => $this->responsesToArray($this->getParents()),
            'partners' => $this->responsesToArray($this->getPartners()),
            'children' => $this->responsesToArray($this->getChildren()),
        ];
    }

    /**
     * @param array $data
     * @return array
     */
    private function responsesToArray(array $data = []): array
    {
        return array_map(fn(BasePerson $value) => $value->toArray(), $data);
    }

    /**
     * @param string $scope
     * @param int $scopeId
     * @return string|null
     */
    public function getBsnByScope(string $scope, int $scopeId): ?string
    {
        switch ($scope) {
            case 'parent':
                $parent = array_values(array_filter(
                    $this->getParents(),
                    fn(ParentPerson $parent) => $parent->getIndex() === $scopeId
                ));
                $bsn = count($parent) ? $parent[0]->getBSN() : null;
                break;
            case 'child':
                $child = array_values(array_filter(
                    $this->getChildren(),
                    fn(Child $child) => $child->getIndex() === $scopeId
                ));
                $bsn = count($child) ? $child[0]->getBSN() : null;
                break;
            case 'partner':
                $partners = $this->getPartners();
                $bsn = count($partners) ? $partners[0]->getBSN() : null;
                break;
            default:
                return null;
        }

        return $bsn;
    }
}
