<?php

namespace App\Services\IdentityProviderService\Support;

use App\Services\IdentityProviderService\Models\IdentityProviderConnection;
use Illuminate\Support\Facades\Config;

class IdentityProviderScimDiscovery
{
    public const string SCHEMA_USER = 'urn:ietf:params:scim:schemas:core:2.0:User';
    public const string SCHEMA_ENTERPRISE_USER = 'urn:ietf:params:scim:schemas:extension:enterprise:2.0:User';
    public const string SCHEMA_FORUS_USER = 'urn:ietf:params:scim:schemas:extension:forus:2.0:User';
    public const string SCHEMA_SCHEMA = 'urn:ietf:params:scim:schemas:core:2.0:Schema';
    public const string SCHEMA_RESOURCE_TYPE = 'urn:ietf:params:scim:schemas:core:2.0:ResourceType';
    public const string SCHEMA_SERVICE_PROVIDER_CONFIG = 'urn:ietf:params:scim:schemas:core:2.0:ServiceProviderConfig';

    /**
     * @return array
     */
    public static function serviceProviderConfig(): array
    {
        return [
            'schemas' => [
                self::SCHEMA_SERVICE_PROVIDER_CONFIG,
            ],
            'patch' => [
                'supported' => true,
            ],
            'bulk' => [
                'supported' => false,
                'maxOperations' => 0,
                'maxPayloadSize' => 0,
            ],
            'filter' => [
                'supported' => true,
                'maxResults' => max(1, (int) Config::get('identity_providers.scim.max_results', 100)),
            ],
            'changePassword' => [
                'supported' => false,
            ],
            'sort' => [
                'supported' => false,
            ],
            'etag' => [
                'supported' => false,
            ],
            'authenticationSchemes' => [[
                'type' => 'oauthbearertoken',
                'name' => 'Bearer token',
                'description' => __('identity_provider.scim.bearer_description'),
                'specUri' => 'https://www.rfc-editor.org/rfc/rfc6750',
                'primary' => true,
            ]],
        ];
    }

    /**
     * @param IdentityProviderConnection $connection
     * @return array
     */
    public static function resourceTypeUser(IdentityProviderConnection $connection): array
    {
        return [
            'schemas' => [self::SCHEMA_RESOURCE_TYPE],
            'id' => 'User',
            'name' => 'User',
            'endpoint' => '/Users',
            'schema' => self::SCHEMA_USER,
            'schemaExtensions' => [['schema' => self::SCHEMA_FORUS_USER, 'required' => false]],
            'meta' => ['resourceType' => 'ResourceType', 'location' => $connection->scimUrl('/ResourceTypes/User')],
        ];
    }

    /**
     * @param IdentityProviderConnection $connection
     * @return array
     */
    public static function schemaUser(IdentityProviderConnection $connection): array
    {
        return [
            'schemas' => [
                self::SCHEMA_SCHEMA,
            ],
            'id' => self::SCHEMA_USER,
            'name' => 'User',
            'description' => __('identity_provider.scim.user_description'),
            'attributes' => [
                self::attribute('id', 'string', true, 'readOnly', 'server', true),
                self::attribute('externalId', 'string', true, 'immutable'),
                self::attribute('userName', 'string', true, uniqueness: 'server'),
                self::attribute('active', 'boolean', true),
                [
                    ...self::attribute('name', 'complex'),
                    'subAttributes' => [
                        self::attribute('givenName', 'string'), self::attribute('familyName', 'string'),
                    ],
                ],
                [
                    ...self::attribute('emails', 'complex', true),
                    'multiValued' => true,
                    'subAttributes' => [
                        self::attribute('value', 'string', true),
                        [...self::attribute('type', 'string'), 'canonicalValues' => ['work']],
                        self::attribute('primary', 'boolean'),
                    ],
                ],
            ],
            'meta' => ['resourceType' => 'Schema', 'location' => $connection->scimUrl('/Schemas/' . self::SCHEMA_USER)],
        ];
    }

    /**
     * @param IdentityProviderConnection $connection
     * @return array
     */
    public static function schemaForusUser(IdentityProviderConnection $connection): array
    {
        return [
            'schemas' => [self::SCHEMA_SCHEMA],
            'id' => self::SCHEMA_FORUS_USER,
            'name' => 'ForusUser',
            'description' => __('identity_provider.scim.user_description'),
            'attributes' => [
                self::attribute('birthDate', 'string'),
                [
                    ...self::attribute('gender', 'string'),
                    'canonicalValues' => array_keys(IdentityProviderScimUserPayload::GENDERS),
                ],
            ],
            'meta' => [
                'resourceType' => 'Schema',
                'location' => $connection->scimUrl('/Schemas/' . self::SCHEMA_FORUS_USER),
            ],
        ];
    }

    /**
     * @param string $name
     * @param string $type
     * @param bool $required
     * @param string $mutability
     * @param string $uniqueness
     * @param bool $caseExact
     * @return array
     */
    protected static function attribute(
        string $name,
        string $type,
        bool $required = false,
        string $mutability = 'readWrite',
        string $uniqueness = 'none',
        bool $caseExact = false,
    ): array {
        return [
            'name' => $name, 'type' => $type, 'multiValued' => false, 'required' => $required,
            'mutability' => $mutability, 'returned' => $name === 'id' ? 'always' : 'default',
            ...$type === 'string' ? ['caseExact' => $caseExact, 'uniqueness' => $uniqueness] : [],
        ];
    }
}
