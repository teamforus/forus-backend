<?php

namespace App\Modules\RequesterImport\Rules;

use App\Models\Organization;
use App\Modules\RequesterImport\Exceptions\RequesterImportException;
use App\Modules\RequesterImport\Services\RequesterImportRowsService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class RequesterImportRowRule implements ValidationRule
{
    /**
     * @param Organization $organization
     */
    public function __construct(protected Organization $organization)
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
        $clientNumber = $value['client_number'] ?? null;

        if (!is_string($bsn) || ($clientNumber !== null && !is_string($clientNumber))) {
            return;
        }

        $service = resolve(RequesterImportRowsService::class);

        try {
            $row = $service->findMatchingRow($this->organization->id, $bsn, $clientNumber);
            $validator = $service->makeRequiredRecordsValidator($this->organization, $value, $row);

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
