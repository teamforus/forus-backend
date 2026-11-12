<?php

namespace App\Modules\VoucherPass\Rules;

use App\Modules\VoucherPass\Models\VoucherPass;
use App\Rules\BsnRule;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class VoucherPassRowsRule implements ValidationRule
{
    public const array FIELDS = [
        'external_id', 'owner_bsn', 'target_bsn', 'type_names', 'fund_ids', 'print_label', 'state', 'print_requested',
    ];

    /**
     * @return array
     */
    public static function rules(): array
    {
        return [
            'external_id' => ['present', 'nullable', 'string', 'max:200'],
            'owner_bsn' => ['bail', 'required', 'string', new BsnRule(exactlyNineDigits: true)],
            'target_bsn' => ['bail', 'required', 'string', new BsnRule(exactlyNineDigits: true)],
            'type_names' => ['present', 'array', 'list'],
            'type_names.*' => ['required', 'string', 'max:200'],
            'fund_ids' => ['present', 'array', 'list'],
            'fund_ids.*' => ['required', 'integer', 'min:1'],
            'print_label' => ['present', 'nullable', 'string', 'max:200'],
            'state' => ['required', Rule::in([VoucherPass::STATE_ACTIVE, VoucherPass::STATE_DEACTIVATED])],
            'print_requested' => ['required', 'boolean'],
            'code' => ['missing'],
        ];
    }

    /**
     * @param array $row
     * @return array
     */
    public static function normalize(array $row): array
    {
        foreach (['external_id', 'owner_bsn', 'target_bsn', 'print_label', 'state'] as $key) {
            if (is_string($row[$key] ?? null)) {
                $row[$key] = trim($row[$key]);

                if ($key === 'external_id' && $row[$key] === '') {
                    $row[$key] = null;
                }
            }
        }

        if (array_key_exists('print_label', $row) && $row['print_label'] === null) {
            $row['print_label'] = '';
        }

        if (is_array($row['type_names'] ?? null)) {
            $row['type_names'] = array_map(
                fn (mixed $name) => is_string($name) ? trim($name) : $name,
                $row['type_names'],
            );
        }

        return $row;
    }

    /**
     * @param string $attribute
     * @param mixed $value
     * @param Closure $fail
     * @return void
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $seen = [];

        foreach ($value as $index => $row) {
            if (!is_array($row)) {
                continue;
            }

            $prefix = $attribute . '.' . $index;
            $row = self::normalize($row);
            $rules = ['row' => [Rule::array(self::FIELDS)]];

            foreach (self::rules() as $key => $rule) {
                $rules['row.' . $key] = $rule;
            }

            $validator = Validator::make(['row' => $row], $rules);

            foreach ($validator->errors()->toArray() as $key => $messages) {
                foreach ($messages as $message) {
                    $fail($prefix . substr($key, 3), $message);
                }
            }

            $uid = $row['external_id'] ?? null;

            if (is_string($uid)) {
                if (isset($seen[$uid])) {
                    $fail($prefix . '.external_id', trans('voucher_pass::validation.duplicate_uid'));
                }

                $seen[$uid] = true;
            }
        }
    }
}
