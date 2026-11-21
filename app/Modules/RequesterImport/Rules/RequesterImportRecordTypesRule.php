<?php

namespace App\Modules\RequesterImport\Rules;

use App\Models\RecordType;
use App\Rules\ProfileRecordValueRule;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Collection;

class RequesterImportRecordTypesRule implements ValidationRule
{
    /**
     * @param Collection<string, RecordType> $recordTypes
     * @param bool $batch
     */
    public function __construct(protected Collection $recordTypes, protected bool $batch = true)
    {
    }

    /**
     * @param string $attribute
     * @param mixed $value
     * @param Closure $fail
     * @return void
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        foreach ($this->batch ? $value : [$value] as $index => $row) {
            if (!is_array($row)) {
                continue;
            }

            $prefix = $this->batch ? $attribute . '.' . $index : $attribute;

            foreach ($row as $key => $recordValue) {
                if ($key === 'bsn') {
                    continue;
                }

                $recordType = $this->recordTypes->get($key);

                if (!$recordType) {
                    $fail($prefix . '.' . $key, trans(
                        'requester_import::validation.record_type_not_configured',
                        ['key' => $key],
                    ));

                    continue;
                }

                (new ProfileRecordValueRule($recordType))->validate(
                    $key,
                    $recordValue,
                    fn (string $message) => $fail($prefix . '.' . $key, $message),
                );
            }
        }
    }
}
