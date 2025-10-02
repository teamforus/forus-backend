<?php

namespace App\Http\Resources\Arr;

use App\Services\PersonBsnApiService\Interfaces\PersonInterface;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read PersonInterface $resource
 */
class IdentityPersonArrResource extends JsonResource
{
    /**
     * @var string[]
     */
    protected array $personFields = [
        'bsn', 'first_name', 'last_name', 'gender', 'nationality', 'age',
        'birth_date', 'birth_place', 'address',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        $person = $this->resource;

        return array_merge($this->baseFieldsToArray($person), [
            'relations' => [
                'parents' => $this->relationToArray($person->geRelated('parents')),
                'partners' => $this->relationToArray($person->geRelated('partners')),
                'children' => $this->relationToArray($person->geRelated('children')),
            ],
        ]);
    }

    /**
     * @param PersonInterface $person
     * @return array
     */
    public function baseFieldsToArray(PersonInterface $person): array
    {
        return [
            'bsn' => $person->getBSN(),
            'name' => $person->getName(),
            'index' => $person->getIndex(),
            'fields' => $this->personToFields($person),
        ];
    }

    /**
     * @param array $relations
     * @return array
     */
    public function relationToArray(array $relations): array
    {
        return array_map(fn (PersonInterface $person) => $this->baseFieldsToArray($person), $relations);
    }

    /**
     * @param PersonInterface $person
     * @return array
     */
    public function personToFields(PersonInterface $person): array
    {
        $personData = $person->toArray();
        $baseFields = [];

        foreach ($this->personFields as $personField) {
            if (array_key_exists($personField, $personData)) {
                $baseFields[$personField] = $personData[$personField];
            }
        }

        return array_reduce(array_keys($baseFields), fn ($arr, $key) => array_merge($arr, [[
            'label' => trans_fb("person_bsn_api.person_fields.$key", $key),
            'value' => $baseFields[$key],
        ]]), []);
    }
}
