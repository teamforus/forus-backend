<?php

namespace App\Services\IdentityProviderService\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ScimFilterRule implements ValidationRule
{
    /**
     * @param string $attribute
     * @param mixed $value
     * @param Closure $fail
     * @return void
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_string($value) && trim($value) === '') {
            return;
        }

        if (self::parse($value) === null) {
            $fail(__('identity_provider.scim.invalid_filter'));
        }
    }

    /**
     * @param mixed $value
     * @return array{attribute: string, value: string}|null
     */
    public static function parse(mixed $value): ?array
    {
        if (!is_string($value) ||
            !preg_match('/^\s*(externalId|userName|id)\s+eq\s+"((?:[^"\\\\]|\\\\.)*)"\s*$/i', $value, $match)) {
            return null;
        }

        $decoded = json_decode('"' . $match[2] . '"', true);

        if (!is_string($decoded) || $decoded === '') {
            return null;
        }

        return ['attribute' => strtolower($match[1]), 'value' => $decoded];
    }
}
