<?php

namespace Tests\Unit\IdentityProviders;

use App\Services\IdentityProviderService\Exceptions\IdentityProviderScimException;
use App\Services\IdentityProviderService\Support\IdentityProviderScimDiscovery;
use App\Services\IdentityProviderService\Support\IdentityProviderScimUserPayload;
use Tests\TestCase;

class IdentityProviderScimUserPayloadTest extends TestCase
{
    private const array CURRENT_USER = [
        'schemas' => [IdentityProviderScimDiscovery::SCHEMA_USER],
        'externalId' => '00000000-0000-4000-8000-000000000001',
        'userName' => 'jane-doe',
        'active' => true,
        'name' => ['givenName' => 'Jane', 'familyName' => 'Doe'],
        'emails' => [['value' => 'contact@example.org', 'type' => 'work', 'primary' => true]],
    ];

    /**
     * @return void
     */
    public function testValidationDiagnosticsContainFieldAndRuleNamesOnly(): void
    {
        foreach ([
            [['schemas' => ['private-schema']], ['schemas' => ['In', 'CoreSchema']]],
            [['schemas' => null], ['schemas' => ['Required']]],
            [['schemas' => 'private-schema'], ['schemas' => ['Array', 'Max']]],
            [
                ['schemas' => [IdentityProviderScimDiscovery::SCHEMA_ENTERPRISE_USER]],
                ['schemas' => ['CoreSchema']],
            ],
            [
                ['schemas' => ['private-key' => IdentityProviderScimDiscovery::SCHEMA_USER]],
                ['schemas' => ['List']],
            ],
            [
                ['schemas' => ['private-key' => 'private-value']],
                ['schemas' => ['In', 'List', 'CoreSchema']],
            ],
            [['name' => 'private-name'], ['name' => ['Structure']]],
            [['emails' => []], ['emails' => ['Structure']]],
            [['active' => 'True'], ['active' => ['Boolean']]],
            [
                ['emails' => [['value' => 'private-email', 'primary' => 'True']]],
                ['emails.primary' => ['Boolean']],
            ],
            [['externalId' => 'private-id'], ['external_id' => ['Uuid']]],
        ] as [$changes, $expected]) {
            try {
                IdentityProviderScimUserPayload::normalize([...self::CURRENT_USER, ...$changes]);

                $this->fail('Invalid user attributes must be rejected.');
            } catch (IdentityProviderScimException $exception) {
                $this->assertSame(400, $exception->status);
                $this->assertSame('invalidValue', $exception->scimType);
                $this->assertSame($expected, $exception->validationErrors);
            }
        }
    }

    /**
     * @throws IdentityProviderScimException
     * @return void
     */
    public function testPatchAppliesUpdatesAndRemovalsInOrder(): void
    {
        $current = self::CURRENT_USER;

        $attributes = IdentityProviderScimUserPayload::normalizePatch([
            'schemas' => [IdentityProviderScimUserPayload::SCHEMA_PATCH],
            'Operations' => [
                ['op' => 'replace', 'value' => ['name' => ['givenName' => 'First change'], 'active' => false]],
                ['op' => 'replace', 'path' => IdentityProviderScimDiscovery::SCHEMA_USER . ':name.givenName', 'value' => 'Final name'],
                ['op' => 'replace', 'path' => 'emails[type eq "work"].value', 'value' => 'changed@example.org'],
                ['op' => 'remove', 'path' => 'name.familyName'],
            ],
        ], $current);

        $this->assertSame([
            'external_id' => $current['externalId'],
            'user_name' => $current['userName'],
            'email' => 'changed@example.org',
            'given_name' => 'Final name',
            'family_name' => '',
            'birth_date' => '',
            'gender' => '',
            'active' => false,
        ], $attributes);
    }

    /**
     * @return void
     */
    public function testOptionalProfileFieldsNormalizeNullEmptyAndCaseInsensitiveValues(): void
    {
        foreach ([
            ['birthDate' => ' 1992-02-29 ', 'gender' => ' FEMALE ', 'expected' => ['1992-02-29', 'female']],
            ['birthDate' => null, 'gender' => null, 'expected' => ['', '']],
            ['birthDate' => '', 'gender' => '', 'expected' => ['', '']],
        ] as $case) {
            $attributes = IdentityProviderScimUserPayload::normalize([
                ...self::CURRENT_USER,
                IdentityProviderScimDiscovery::SCHEMA_FORUS_USER => [
                    'BIRTHDATE' => $case['birthDate'], 'GENDER' => $case['gender'],
                ],
            ]);

            $this->assertSame($case['expected'], [$attributes['birth_date'], $attributes['gender']]);
        }
    }

    /**
     * @return void
     */
    public function testInvalidProfileFieldsAreRejected(): void
    {
        foreach ([
            ['birthDate' => '1991-02-29'],
            ['birthDate' => '10-05-1990'],
            ['birthDate' => '1990-05-10T00:00:00Z'],
            ['birthDate' => now()->addDay()->format('Y-m-d')],
            ['birthDate' => 19900510],
            ['birthDate' => []],
            ['gender' => 'invalid'],
            ['gender' => 1],
            ['gender' => []],
            ['gender' => 'male', 'GENDER' => 'female'],
            ['birthDate' => '1990-05-10', 'unsupported' => 'value'],
        ] as $profile) {
            try {
                IdentityProviderScimUserPayload::normalize([
                    ...self::CURRENT_USER, IdentityProviderScimDiscovery::SCHEMA_FORUS_USER => $profile,
                ]);

                $this->fail('Invalid profile attributes must be rejected.');
            } catch (IdentityProviderScimException $exception) {
                $this->assertSame(400, $exception->status);
                $this->assertSame('invalidValue', $exception->scimType);
            }
        }
    }

    /**
     * @return void
     */
    public function testPatchRejectsInvalidActiveValues(): void
    {
        foreach ([0, 1, '0', '1', 'yes', 'no', '', null, []] as $value) {
            try {
                IdentityProviderScimUserPayload::normalizePatch([
                    'schemas' => [IdentityProviderScimUserPayload::SCHEMA_PATCH],
                    'Operations' => [['op' => 'replace', 'path' => 'active', 'value' => $value]],
                ], self::CURRENT_USER);

                $this->fail('Invalid active values must be rejected.');
            } catch (IdentityProviderScimException $exception) {
                $this->assertSame(400, $exception->status);
                $this->assertSame('invalidValue', $exception->scimType);
            }
        }
    }

    /**
     * @return void
     */
    public function testPatchRejectsExternalIdChange(): void
    {
        $current = self::CURRENT_USER;

        try {
            IdentityProviderScimUserPayload::normalizePatch([
                'schemas' => [IdentityProviderScimUserPayload::SCHEMA_PATCH],
                'Operations' => [
                    ['op' => 'replace', 'path' => 'name.givenName', 'value' => 'Janet'],
                    ['op' => 'replace', 'path' => 'externalId', 'value' => '11111111-1111-4111-8111-111111111111'],
                ],
            ], $current);

            $this->fail('Changing the external object must fail the complete PATCH.');
        } catch (IdentityProviderScimException $exception) {
            $this->assertSame(400, $exception->status);
            $this->assertSame('mutability', $exception->scimType);
        }
    }
}
