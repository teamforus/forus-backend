<?php

namespace Tests\Traits;

use App\Enums\RecordValidationKey;
use App\Models\Organization;
use App\Models\RecordType;
use App\Modules\RequesterImport\Models\RequesterImportRow;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Throwable;

trait MakesTestRequesterImports
{
    use MakesTestOrganizations;

    /**
     * @throws Throwable
     * @return array{
     *     organization: Organization, name: RecordType, address: RecordType, date: RecordType, choice: RecordType
     * }
     */
    protected function makeRequesterImportFixture(): array
    {
        $organization = $this->makeTestOrganization($this->makeIdentity($this->makeUniqueEmail()), [
            'is_sponsor' => true,
            'allow_requester_imports' => true,
            'allow_profiles' => true,
            'bsn_enabled' => true,
        ]);

        $name = RecordType::create([
            'key' => 'c05_name_' . $organization->id,
            'type' => RecordType::TYPE_STRING,
            'requester_imports' => true,
            'validation_key' => RecordValidationKey::PERSON_NAME,
        ]);

        $address = RecordType::create([
            'key' => 'c05_address_' . $organization->id,
            'type' => RecordType::TYPE_STRING,
            'requester_imports' => true,
            'organization_id' => $organization->id,
        ]);

        $date = RecordType::create([
            'key' => 'c05_date_' . $organization->id,
            'type' => RecordType::TYPE_DATE,
            'requester_imports' => true,
            'organization_id' => $organization->id,
        ]);

        $choice = RecordType::create([
            'key' => 'c05_choice_' . $organization->id,
            'type' => RecordType::TYPE_SELECT,
            'requester_imports' => true,
            'organization_id' => $organization->id,
        ]);

        $choice->record_type_options()->createMany([['value' => 'first'], ['value' => 'second']]);
        RecordType::findByKey('client_number')->update(['requester_imports' => true, 'organization_id' => null]);

        $mappingKey = 'c05_' . $organization->id;
        $organization->forceFill(['requester_import_mapping_key' => $mappingKey])->save();

        Config::set('requester_import.mappings.' . $mappingKey, ['fields' => [
            'bsn' => ['column' => 'BSN', 'required' => true],
            'client_number' => ['column' => 'Client number', 'required' => false],
            $name->key => ['column' => 'Name', 'required' => true],
            $address->key => ['column' => 'Address', 'required' => false],
            $date->key => ['column' => 'Date', 'required' => false],
            $choice->key => ['column' => 'Choice', 'required' => false],
        ]]);

        return compact('organization', 'name', 'address', 'date', 'choice');
    }

    /**
     * @param RecordType $name
     * @param array $values
     * @throws Throwable
     * @return array<string, string|null>
     */
    protected function makeRequesterImportData(RecordType $name, array $values = []): array
    {
        return [
            'bsn' => $this->makeUniqueBsn(),
            'client_number' => 'client_' . token_generator()->generate(12),
            $name->key => 'Taylor',
            ...$values,
        ];
    }

    /**
     * @param Organization $organization
     * @param array<string, string|null> $data
     * @return RequesterImportRow
     */
    protected function makeRequesterImportRow(Organization $organization, array $data): RequesterImportRow
    {
        $row = RequesterImportRow::create(['organization_id' => $organization->id, 'bsn' => $data['bsn']]);

        foreach (Arr::except($data, ['bsn']) as $key => $value) {
            $row->records()->create([
                'organization_id' => $organization->id,
                'record_type_key' => $key,
                'value' => $value ?? '',
            ]);
        }

        return $row;
    }
}
