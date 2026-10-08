<?php

namespace App\Services\IdentityProviderService\Support;

use App\Services\IdentityProviderService\Exceptions\IdentityProviderScimException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class IdentityProviderScimUserPayload
{
    public const string SCHEMA_PATCH = 'urn:ietf:params:scim:api:messages:2.0:PatchOp';

    public const array EVENT_FIELDS = [
        'externalId', 'userName', 'active', 'name', 'name.givenName', 'name.familyName', 'emails',
        'birthDate', 'gender',
    ];

    public const array GENDERS = [
        'male' => 'mannelijk',
        'female' => 'vrouwelijk',
        'unknown' => 'onbekend',
        'unspecified' => 'niet gespecificeerd',
    ];

    protected const string WORK_EMAIL_PATH_PATTERN = '/^emails\s*\[\s*type\s+eq\s*"work"\s*]\.value$/';

    protected const array USER_ATTRIBUTES = [
        'schemas', 'externalid', 'username', 'active', 'name', 'emails', 'id', 'meta',
        IdentityProviderScimDiscovery::SCHEMA_FORUS_USER,
    ];

    /**
     * @param array $payload
     * @return array
     */
    public static function eventContext(array $payload): array
    {
        $payload = array_change_key_case($payload);
        $values = $payload;
        $fields = self::eventAttributeFields($payload);

        foreach (array_slice(is_array($payload['operations'] ?? null) ? $payload['operations'] : [], 0, 20) as $operation) {
            if (!is_array($operation)) {
                continue;
            }

            $operation = array_change_key_case($operation);

            if (!isset($operation['path']) && is_array($operation['value'] ?? null)) {
                $attributes = array_change_key_case($operation['value']);
                $fields = [...$fields, ...self::eventAttributeFields($attributes)];
                $values = array_replace($values, $attributes);
            } elseif (is_string($operation['path'] ?? null)) {
                $path = self::normalizePatchPath($operation['path']);
                $path = preg_match(self::WORK_EMAIL_PATH_PATTERN, $path) ? 'emails' : $path;
                $fields = [...$fields, ...self::eventAttributeFields([$path => $operation['value'] ?? null])];
                $values[$path] = $operation['value'] ?? null;
            }
        }

        $email = $values['emails'] ?? null;

        if (is_array($email) && is_array($email[0] ?? null)) {
            $email = array_change_key_case($email[0])['value'] ?? null;
        }

        $context = [
            'requested_fields' => array_values(array_filter(
                self::EVENT_FIELDS,
                fn (string $field) => in_array(strtolower($field), $fields, true),
            )),
        ];

        if (is_string($email) && Validator::make(['email' => $email], ['email' => 'required|email:rfc|max:200'])->passes()) {
            $context['account_email'] = $email;
        }

        $externalId = $values['externalid'] ?? null;

        if (is_string($externalId) && Validator::make(['id' => $externalId], ['id' => 'required|uuid'])->passes()) {
            $context['external_id'] = strtolower($externalId);
        }

        return $context;
    }

    /**
     * @param array $payload
     * @param string|null $expectedExternalId
     * @throws IdentityProviderScimException
     * @return array{
     *     external_id: string, user_name: string, email: string,
     *     given_name: string, family_name: string, birth_date: string, gender: string, active: bool
     * }
     */
    public static function normalize(array $payload, ?string $expectedExternalId = null): array
    {
        $payload = self::attributes($payload, self::USER_ATTRIBUTES);

        $name = $payload['name'] ?? [];
        $emails = $payload['emails'] ?? null;
        $profile = $payload[strtolower(IdentityProviderScimDiscovery::SCHEMA_FORUS_USER)] ?? [];

        foreach ([
            'name' => is_array($name),
            'profile' => is_array($profile),
            'emails' => is_array($emails) && array_is_list($emails) && count($emails) === 1 && is_array($emails[0]),
        ] as $field => $valid) {
            if (!$valid) {
                throw new IdentityProviderScimException(
                    __('identity_provider.scim.invalid_user'),
                    400,
                    'invalidValue',
                    validationErrors: [$field => ['Structure']],
                );
            }
        }

        $name = self::attributes($name, ['givenname', 'familyname']);
        $email = self::attributes($emails[0], ['value', 'type', 'primary']);
        $profile = self::attributes($profile, ['birthdate', 'gender']);

        foreach ([
            'active' => is_bool($payload['active'] ?? null),
            'emails.primary' => !array_key_exists('primary', $email) || is_bool($email['primary']),
        ] as $field => $valid) {
            if (!$valid) {
                throw new IdentityProviderScimException(
                    __('identity_provider.scim.invalid_user'),
                    400,
                    'invalidValue',
                    validationErrors: [$field => ['Boolean']],
                );
            }
        }

        $attributes = [
            'external_id' => $payload['externalid'] ?? null,
            'user_name' => $payload['username'] ?? null,
            'email' => $email['value'] ?? null,
            'given_name' => $name['givenname'] ?? '',
            'family_name' => $name['familyname'] ?? '',
            'birth_date' => $profile['birthdate'] ?? '',
            'gender' => $profile['gender'] ?? '',
            'active' => $payload['active'],
        ];

        foreach (['external_id', 'user_name', 'email', 'given_name', 'family_name', 'birth_date', 'gender'] as $key) {
            if (is_string($attributes[$key])) {
                $attributes[$key] = trim($attributes[$key]);
            }
        }

        if (is_string($attributes['gender'])) {
            $attributes['gender'] = strtolower($attributes['gender']);
        }

        $validator = Validator::make([
            ...$attributes,
            'schemas' => $payload['schemas'] ?? null,
            'email_type' => $email['type'] ?? null,
        ], [
            'schemas' => 'required|array|min:1|max:3',
            'schemas.*' => ['required', 'string', 'distinct', Rule::in([
                IdentityProviderScimDiscovery::SCHEMA_USER, IdentityProviderScimDiscovery::SCHEMA_FORUS_USER,
                IdentityProviderScimDiscovery::SCHEMA_ENTERPRISE_USER,
            ])],
            'external_id' => 'required|uuid',
            'user_name' => 'required|string|max:255',
            'email' => 'required|string|email:rfc|max:200',
            'email_type' => 'nullable|string|in:work',
            'given_name' => 'string|max:400',
            'family_name' => 'string|max:400',
            'birth_date' => 'nullable|string|date_format:Y-m-d|before_or_equal:today',
            'gender' => ['string', Rule::in(array_keys(self::GENDERS))],
        ]);

        $validationErrors = [];

        if ($validator->fails()) {
            foreach ($validator->failed() as $field => $rules) {
                $field = explode('.', $field)[0];
                $validationErrors[$field] = array_values(array_unique([
                    ...($validationErrors[$field] ?? []), ...array_keys($rules),
                ]));
            }
        }

        if (is_array($payload['schemas'] ?? null)) {
            if (!array_is_list($payload['schemas'])) {
                $validationErrors['schemas'][] = 'List';
            }

            if (!in_array(IdentityProviderScimDiscovery::SCHEMA_USER, $payload['schemas'], true)) {
                $validationErrors['schemas'][] = 'CoreSchema';
            }
        }

        if ($validationErrors) {
            throw new IdentityProviderScimException(
                __('identity_provider.scim.invalid_user'),
                400,
                'invalidValue',
                validationErrors: $validationErrors,
            );
        }

        $attributes['external_id'] = strtolower($attributes['external_id']);

        if ($expectedExternalId !== null && $attributes['external_id'] !== strtolower($expectedExternalId)) {
            throw new IdentityProviderScimException(__('identity_provider.scim.immutable_attribute'), 400, 'mutability');
        }

        return $attributes;
    }

    /**
     * @param array $payload
     * @param array $current
     * @throws IdentityProviderScimException
     * @return array
     */
    public static function normalizePatch(array $payload, array $current): array
    {
        $payload = self::attributes($payload, ['schemas', 'operations']);
        $operations = $payload['operations'] ?? null;

        if (($payload['schemas'] ?? null) !== [self::SCHEMA_PATCH] || !is_array($operations) ||
            !array_is_list($operations) || count($operations) < 1 || count($operations) > 20) {
            throw new IdentityProviderScimException(__('identity_provider.scim.invalid_patch'), 400, 'invalidSyntax');
        }

        $current = array_change_key_case($current);
        $expectedExternalId = $current['externalid'];

        foreach ($operations as $operation) {
            $current = self::applyPatchOperation($current, self::normalizePatchOperation($operation));
        }

        return self::normalize($current, $expectedExternalId);
    }

    /**
     * @param mixed $operation
     * @throws IdentityProviderScimException
     * @return array{op: string, path?: string, value?: mixed}
     */
    protected static function normalizePatchOperation(mixed $operation): array
    {
        if (!is_array($operation)) {
            throw new IdentityProviderScimException(__('identity_provider.scim.invalid_patch'), 400, 'invalidSyntax');
        }

        $operation = self::attributes($operation, ['op', 'path', 'value']);

        if (!is_string($operation['op'] ?? null)) {
            throw new IdentityProviderScimException(__('identity_provider.scim.invalid_patch'), 400, 'invalidSyntax');
        }

        $operation['op'] = strtolower($operation['op']);
        self::assertPatchOperationType($operation);

        if (array_key_exists('path', $operation)) {
            self::assertPatchOperationWithPath($operation);
        } else {
            self::assertPatchOperationWithoutPath($operation);

            $operation['value'] = self::attributes($operation['value'], [
                ...self::USER_ATTRIBUTES, 'name.givenname', 'name.familyname',
                IdentityProviderScimDiscovery::SCHEMA_FORUS_USER . ':birthDate',
                IdentityProviderScimDiscovery::SCHEMA_FORUS_USER . ':gender',
            ]);
        }

        return $operation;
    }

    /**
     * @param array $operation
     * @throws IdentityProviderScimException
     * @return void
     */
    protected static function assertPatchOperationType(array $operation): void
    {
        if (!in_array($operation['op'], ['add', 'replace', 'remove'], true)) {
            throw new IdentityProviderScimException(__('identity_provider.scim.invalid_patch'), 400, 'invalidSyntax');
        }

        if ($operation['op'] !== 'remove' && !array_key_exists('value', $operation)) {
            throw new IdentityProviderScimException(__('identity_provider.scim.invalid_patch'), 400, 'invalidSyntax');
        }
    }

    /**
     * @param array $operation
     * @throws IdentityProviderScimException
     * @return void
     */
    protected static function assertPatchOperationWithPath(array $operation): void
    {
        if (!is_string($operation['path']) || trim($operation['path']) === '') {
            throw new IdentityProviderScimException(__('identity_provider.scim.invalid_patch_path'), 400, 'invalidPath');
        }
    }

    /**
     * @param array $operation
     * @throws IdentityProviderScimException
     * @return void
     */
    protected static function assertPatchOperationWithoutPath(array $operation): void
    {
        if ($operation['op'] === 'remove') {
            throw new IdentityProviderScimException(__('identity_provider.scim.invalid_patch'), 400, 'invalidSyntax');
        }

        if (!is_array($operation['value']) || array_is_list($operation['value'])) {
            throw new IdentityProviderScimException(__('identity_provider.scim.invalid_patch'), 400, 'invalidSyntax');
        }
    }

    /**
     * @param array $current
     * @param array{op: string, path?: string, value?: mixed} $operation
     * @throws IdentityProviderScimException
     * @return array
     */
    protected static function applyPatchOperation(array $current, array $operation): array
    {
        if (array_key_exists('path', $operation)) {
            return self::applyPatchValue($current, $operation['op'], $operation['path'], $operation['value'] ?? null);
        }

        foreach ($operation['value'] as $path => $value) {
            $current = self::applyPatchValue($current, $operation['op'], $path, $value);
        }

        return $current;
    }

    /**
     * @param array $current
     * @param string $op
     * @param string $path
     * @param mixed $value
     * @throws IdentityProviderScimException
     * @return array
     */
    protected static function applyPatchValue(array $current, string $op, string $path, mixed $value): array
    {
        $path = self::normalizePatchPath($path);
        $remove = $op === 'remove';

        if (in_array($path, ['id', 'meta', 'schemas'], true) ||
            ($remove && in_array($path, ['externalid', 'username', 'active', 'emails'], true))) {
            throw new IdentityProviderScimException(__('identity_provider.scim.immutable_attribute'), 400, 'mutability');
        }

        if ($path === 'active' && is_string($value)) {
            $value = match (strtolower($value)) {
                'true' => true,
                'false' => false,
                default => $value,
            };
        }

        if (in_array($path, ['externalid', 'username', 'active'], true)) {
            $current[$path] = $value;

            return $current;
        }

        $profileSchema = strtolower(IdentityProviderScimDiscovery::SCHEMA_FORUS_USER);

        if ($path === $profileSchema) {
            if (!$remove && $value !== null && !is_array($value)) {
                throw new IdentityProviderScimException(__('identity_provider.scim.invalid_user'), 400, 'invalidValue');
            }

            $current[$profileSchema] = $remove || $value === null ? [] : array_replace(
                array_change_key_case($current[$profileSchema] ?? []),
                self::attributes($value, ['birthdate', 'gender']),
            );

            return $current;
        }

        if (in_array($path, [$profileSchema . ':birthdate', $profileSchema . ':gender'], true)) {
            $current[$profileSchema] = array_change_key_case($current[$profileSchema] ?? []);
            $current[$profileSchema][substr($path, strlen($profileSchema) + 1)] = $remove ? '' : $value;

            return $current;
        }

        if ($path === 'name') {
            if (!$remove && $value !== null && !is_array($value)) {
                throw new IdentityProviderScimException(__('identity_provider.scim.invalid_user'), 400, 'invalidValue');
            }

            $current['name'] = $remove || $value === null
                ? []
                : array_replace(
                    array_change_key_case($current['name'] ?? []),
                    self::attributes($value, ['givenname', 'familyname']),
                );

            return $current;
        }

        if (in_array($path, ['name.givenname', 'name.familyname'], true)) {
            $current['name'] = array_change_key_case($current['name'] ?? []);
            $current['name'][substr($path, 5)] = $remove ? '' : $value;

            return $current;
        }

        if ($path === 'emails') {
            if (!is_array($value) || !array_is_list($value) || count($value) !== 1 || !is_array($value[0])) {
                throw new IdentityProviderScimException(__('identity_provider.scim.invalid_user'), 400, 'invalidValue');
            }

            $value[0] = self::attributes($value[0], ['value', 'type', 'primary']);

            if ($op === 'add') {
                $value = array_values(array_unique([...$current['emails'], ...$value], SORT_REGULAR));

                if (count($value) !== 1) {
                    throw new IdentityProviderScimException(__('identity_provider.scim.invalid_user'), 400, 'invalidValue');
                }
            }

            $current['emails'] = $value;

            return $current;
        }

        if (preg_match(self::WORK_EMAIL_PATH_PATTERN, $path)) {
            if ($remove) {
                throw new IdentityProviderScimException(__('identity_provider.scim.immutable_attribute'), 400, 'mutability');
            }

            $current['emails'][0]['value'] = $value;

            return $current;
        }

        throw new IdentityProviderScimException(__('identity_provider.scim.invalid_patch_path'), 400, 'invalidPath');
    }

    /**
     * @param string $path
     * @return string
     */
    protected static function normalizePatchPath(string $path): string
    {
        $path = strtolower(trim($path));
        $prefix = strtolower(IdentityProviderScimDiscovery::SCHEMA_USER) . ':';

        return str_starts_with($path, $prefix) ? substr($path, strlen($prefix)) : $path;
    }

    /**
     * @param array $attributes
     * @return array
     */
    protected static function eventAttributeFields(array $attributes): array
    {
        $attributes = array_change_key_case($attributes);
        $schema = strtolower(IdentityProviderScimDiscovery::SCHEMA_FORUS_USER);
        $profile = is_array($attributes[$schema] ?? null) ? array_change_key_case($attributes[$schema]) : [];
        $fields = array_keys($attributes);

        foreach (['birthdate', 'gender'] as $field) {
            if (array_key_exists($schema . ':' . $field, $attributes) || array_key_exists($field, $profile) ||
                (array_key_exists($schema, $attributes) && $attributes[$schema] === null)) {
                $fields[] = $field;
            }
        }

        return $fields;
    }

    /**
     * @param array $attributes
     * @param array $allowed
     * @throws IdentityProviderScimException
     * @return array
     */
    protected static function attributes(array $attributes, array $allowed): array
    {
        $normalized = array_change_key_case($attributes);

        if (count($normalized) !== count($attributes) ||
            array_diff(array_keys($normalized), array_map('strtolower', $allowed))) {
            throw new IdentityProviderScimException(
                __('identity_provider.scim.unsupported_attribute'),
                400,
                'invalidValue',
                'unsupported_attribute',
            );
        }

        return $normalized;
    }
}
