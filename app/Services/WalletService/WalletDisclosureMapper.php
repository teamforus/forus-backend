<?php

namespace App\Services\WalletService;

use App\Models\Fund;
use App\Models\FundCriterion;
use App\Rules\FundRequests\BaseFundRequestRule;
use DateTimeImmutable;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class WalletDisclosureMapper
{
    protected const array RECORD_MAPPING = [
        'given_name' => 'first_name',
        'family_name' => 'last_name',
        'initials' => 'initials',
        'birth_date' => 'date_of_birth',
        'gender' => 'gender',
        'last_name_prefix' => 'last_name_prefix',
        'wallet_bsn' => 'bsn',
        'street' => 'street_name',
        'house_number' => 'house_number',
        'postal_code' => 'postal_code',
        'city' => 'city',
        'municipality_name' => 'municipality',
    ];

    /**
     * @param Fund $fund
     * @param array $payload Verified disclosure payload.
     * @throws ValidationException
     * @return array
     */
    public function map(Fund $fund, array $payload): array
    {
        $values = [];

        foreach (self::RECORD_MAPPING as $recordKey => $claimKey) {
            $value = Arr::get($payload, "mapping.$claimKey.value");
            $values[$recordKey] = is_string($value) ? trim($value) : $value;

            if ($values[$recordKey] === '') {
                $values[$recordKey] = null;
            }
        }

        if (is_string($values['gender'])) {
            $values['gender'] = match (strtolower($values['gender'])) {
                'm', 'mannelijk' => 'mannelijk',
                'v', 'f', 'vrouwelijk' => 'vrouwelijk',
                'onbekend' => 'onbekend',
                'niet gespecificeerd' => 'niet gespecificeerd',
                default => $values['gender'],
            };
        }

        if (is_string($values['birth_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/D', $values['birth_date'])) {
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', $values['birth_date']);

            if ($date && $date->format('Y-m-d') === $values['birth_date']) {
                $values['birth_date'] = $date->format('d-m-Y');
            }
        }

        if (is_int($values['house_number'])) {
            $values['house_number'] = (string) $values['house_number'];
        }

        $values['house_number_addition'] = null;

        if (is_string($values['house_number']) &&
            preg_match('/^(\d+)\s*-?\s*([a-zA-Z](?:\s?\d+)?)$/D', $values['house_number'], $matches)) {
            $values['house_number'] = $matches[1];
            $values['house_number_addition'] = $matches[2];
        }

        return $this->validateRecords($fund, $values);
    }

    /**
     * @param Fund $fund
     * @param array $values
     * @throws ValidationException
     * @return array
     */
    public function validateRecords(Fund $fund, array $values): array
    {
        $fund->loadMissing(['criteria.record_type.record_type_options', 'criteria.fund_criterion_rules']);

        $records = [];
        $errors = [];

        foreach ($fund->criteria->where('fill_type', FundCriterion::FILL_TYPE_PREFILL) as $criterion) {
            if ($criterion->isExcludedByRules($values)) {
                continue;
            }

            $key = $criterion->record_type_key;
            $value = $values[$key] ?? null;
            $label = $criterion->title ?: $key;
            $rule = BaseFundRequestRule::recordTypeRuleFor($criterion, $label);

            if (!$rule || !$rule->passes($key, $value)) {
                $errors[$key] = [$rule?->message() ?: trans('validation.in', ['attribute' => $label])];
            }

            $records[$key] = $value;
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        return $records;
    }
}
