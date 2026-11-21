<?php

namespace Tests\Unit;

use App\Enums\RecordValidationKey;
use App\Models\RecordType;
use App\Models\RecordTypeOption;
use App\Models\RecordTypeTranslation;
use App\Rules\ProfileRecordValueRule;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ProfileRecordValueRuleTest extends TestCase
{
    /**
     * @return void
     */
    public function testValidatesDateType(): void
    {
        $this->travelTo('2026-11-12 12:00:00');
        $date = $this->makeRecordType(RecordType::TYPE_DATE);

        $this->assertRecordValue($date, '2026-11-12', true);
        $this->assertRecordValue($date, '2026-11-13', true);
        $this->assertRecordValue($date, '2026-02-30', false);
        $this->assertRecordValue($date, '12-11-2026', false);
    }

    /**
     * @return void
     */
    public function testValidatesBirthDatePreset(): void
    {
        $this->travelTo('2026-11-12 12:00:00');
        $birthDate = $this->makeRecordType(RecordType::TYPE_DATE, RecordValidationKey::BIRTH_DATE);

        $this->assertRecordValue($birthDate, '2026-11-12', true);
        $this->assertRecordValue($birthDate, '2026-11-13', false);
    }

    /**
     * @return void
     */
    public function testValidatesNumberType(): void
    {
        $recordType = $this->makeRecordType(RecordType::TYPE_NUMBER);

        $this->assertRecordValue($recordType, '0', true);
        $this->assertRecordValue($recordType, '12.5', true);
        $this->assertRecordValue($recordType, 'twelve', false);
    }

    /**
     * @return void
     */
    public function testValidatesSelectAndBoolTypes(): void
    {
        $select = $this->makeRecordType(RecordType::TYPE_SELECT, options: ['first', 'second']);
        $number = $this->makeRecordType(RecordType::TYPE_SELECT_NUMBER, options: ['0', '2', 'text']);
        $boolean = $this->makeRecordType(RecordType::TYPE_BOOL);

        $this->assertRecordValue($select, 'first', true);
        $this->assertRecordValue($select, 'third', false);
        $this->assertRecordValue($number, '0', true);
        $this->assertRecordValue($number, '2', true);
        $this->assertRecordValue($number, '3', false);
        $this->assertRecordValue($number, 'text', false);
        $this->assertRecordValue($boolean, RecordType::TYPE_BOOL_OPTION_YES, true);
        $this->assertRecordValue($boolean, RecordType::TYPE_BOOL_OPTION_NO, true);
        $this->assertRecordValue($boolean, 'other', false);
    }

    /**
     * @return void
     */
    public function testRejectsIncompatibleValidationPreset(): void
    {
        $this->assertRecordValue(
            $this->makeRecordType(RecordType::TYPE_DATE, RecordValidationKey::PERSON_NAME),
            '2000-01-01',
            false,
        );

        $this->assertRecordValue(
            $this->makeRecordType(RecordType::TYPE_STRING, RecordValidationKey::BIRTH_DATE),
            '2000-01-01',
            false,
        );
    }

    /**
     * @return void
     */
    public function testValidatesPersonNamePreset(): void
    {
        $recordType = $this->makeRecordType(RecordType::TYPE_STRING, RecordValidationKey::PERSON_NAME);

        $this->assertRecordValue($recordType, 'A', false);
        $this->assertRecordValue($recordType, 'Al', true);
        $this->assertRecordValue($recordType, str_repeat('a', 40), true);
        $this->assertRecordValue($recordType, str_repeat('a', 41), false);
    }

    /**
     * @return void
     */
    public function testAllowsClearingOptionalRecordValues(): void
    {
        foreach ([RecordType::TYPE_STRING, RecordType::TYPE_NUMBER, RecordType::TYPE_DATE] as $type) {
            $recordType = $this->makeRecordType($type);

            foreach ([null, ''] as $value) {
                (new ProfileRecordValueRule($recordType))->validate(
                    'value',
                    $value,
                    fn (string $message) => $this->fail($message),
                );

                $this->assertRecordValue($recordType, $value, true);
            }
        }
    }

    /**
     * @return void
     */
    public function testValidatesBsnPreset(): void
    {
        $recordType = $this->makeRecordType(RecordType::TYPE_STRING, RecordValidationKey::BSN);

        $this->assertRecordValue($recordType, '012345672', true);
        $this->assertRecordValue($recordType, '12345672', false);
        $this->assertRecordValue($recordType, '012345673', false);
    }

    /**
     * @param string $type
     * @param RecordValidationKey|null $preset
     * @param string[] $options
     * @return RecordType
     */
    private function makeRecordType(
        string $type,
        ?RecordValidationKey $preset = null,
        array $options = [],
    ): RecordType {
        $recordType = new RecordType(['key' => 'test_record', 'type' => $type, 'validation_key' => $preset]);

        $recordType->setRelation('translations', new Collection([
            (new RecordTypeTranslation())->forceFill(['locale' => app()->getLocale(), 'name' => 'Test record']),
        ]));

        $recordType->setRelation('record_type_options', new Collection(array_map(
            fn (string $value) => (new RecordTypeOption(['value' => $value]))
                ->setRelation('translations', new Collection()),
            $options,
        )));

        return $recordType;
    }

    /**
     * @param RecordType $recordType
     * @param string|null $value
     * @param bool $valid
     * @return void
     */
    private function assertRecordValue(RecordType $recordType, ?string $value, bool $valid): void
    {
        $validator = Validator::make(['value' => $value], [
            'value' => ['nullable', new ProfileRecordValueRule($recordType)],
        ]);

        $this->assertSame($valid, $validator->passes(), $recordType->type . ': ' . var_export($value, true));
    }
}
