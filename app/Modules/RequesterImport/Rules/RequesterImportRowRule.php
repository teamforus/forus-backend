<?php

namespace App\Modules\RequesterImport\Rules;

use App\Modules\RequesterImport\Exceptions\RequesterImportException;
use App\Modules\RequesterImport\Services\RequesterImportRowsService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class RequesterImportRowRule implements ValidationRule
{
    /**
     * @param int $organizationId
     */
    public function __construct(protected int $organizationId)
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
        $bsn = $value['bsn'] ?? null;
        $clientUuid = $value['client_uuid'] ?? null;

        if (!is_string($bsn) || ($clientUuid !== null && !is_string($clientUuid))) {
            return;
        }

        $service = resolve(RequesterImportRowsService::class);

        try {
            if ($service->findMatchingRow($this->organizationId, $bsn, $clientUuid)) {
                return;
            }

            $validator = $service->makeRequiredRecordsValidator($value);

            if (!$validator->fails()) {
                return;
            }

            $errors = $validator->errors()->toArray();
        } catch (RequesterImportException $exception) {
            $errors = $exception->errors();
        }

        foreach ($errors as $key => $messages) {
            foreach ($messages as $message) {
                $fail($attribute . '.' . $key, $message);
            }
        }
    }
}
