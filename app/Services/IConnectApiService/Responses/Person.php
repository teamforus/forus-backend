<?php

namespace App\Services\IConnectApiService\Responses;

/**
 * Class Person
 * @package App\Services\IConnectApiService\Responses
 */
class Person extends BasePerson
{
    /** @var array  */
    protected $parents;

    /** @var array  */
    protected $children;

    /** @var array  */
    protected $partners;

    /**
     * @param array $data
     */
    public function __construct(array $data = []) {
        parent::__construct($data);

        $this->parents = [];
        $parentsRaw = $this->raw['_embedded']['ouders'] ?? [];
        if (is_array($parentsRaw) && count($parentsRaw)) {
            foreach ($parentsRaw as $item) {
                $this->parents[] = new ParentPerson($item);
            }
        }

        $this->children = [];
        $childrenRaw = $this->raw['_embedded']['kinderen'] ?? [];
        if (is_array($childrenRaw) && count($childrenRaw)) {
            foreach ($childrenRaw as $item) {
                $this->children[] = new Child($item);
            }
        }

        $this->partners = [];
        $partnersRaw = $this->raw['_embedded']['partners'] ?? [];
        if (is_array($partnersRaw) && count($partnersRaw)) {
            foreach ($partnersRaw as $item) {
                $this->partners[] = new Partner($item);
            }
        }
    }

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
        return (bool)($this->raw['overlijden']['indicatieOverleden'] ?? false);
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
            'address' => ($this->raw['verblijfplaats']['adresregel1'] ?? '') .
                ($this->raw['verblijfplaats']['adresregel2'] ?? '') .
                ($this->raw['verblijfplaats']['adresregel3'] ?? '')
        ];
    }

    /**
     * @return array
     */
    public function getParents(): array
    {
        return $this->parents;
    }

    /**
     * @return array
     */
    public function getPartners(): array
    {
        return $this->partners;
    }

    /**
     * @return array
     */
    public function getChildren(): array
    {
        return $this->children;
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
        $result = [];
        array_walk($data, static function ($value, $key) use (&$result) {
            /** @var BasePerson $value */
            $result[] = $value->toArray();
        });

        return $result;
    }
}
