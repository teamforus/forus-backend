<?php

namespace App\Modules\RequesterImport\Data;

use JsonSerializable;

readonly class RequesterImportSyncPreview implements JsonSerializable
{
    /**
     * @param int $id
     * @param string $bsn
     * @param int|null $profileId
     * @param 'create'|'update'|'unchanged'|'blocked' $action
     * @param array<int, RequesterImportProfileRecordChange> $changes
     * @param array<string, array<string>> $errors
     * @param string|null $code
     * @param string|null $diagnosticId
     */
    public function __construct(
        public int $id,
        public string $bsn,
        public ?int $profileId,
        public string $action,
        public array $changes = [],
        public array $errors = [],
        public ?string $code = null,
        public ?string $diagnosticId = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'bsn' => $this->bsn,
            'profile_id' => $this->profileId,
            'action' => $this->action,
            'changes' => $this->changes,
            'errors' => $this->errors,
            'code' => $this->code,
            'diagnostic_id' => $this->diagnosticId,
        ];
    }
}
