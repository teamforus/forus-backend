<?php

namespace App\Modules\RequesterImport\Data;

use JsonSerializable;

readonly class RequesterImportRowPreview implements JsonSerializable
{
    /**
     * @param int $index
     * @param int|null $id
     * @param 'create'|'update'|'unchanged' $action
     * @param array<int, RequesterImportRowRecordChange> $changes
     */
    public function __construct(
        public int $index,
        public ?int $id,
        public string $action,
        public array $changes,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'index' => $this->index,
            'id' => $this->id,
            'action' => $this->action,
            'changes' => $this->changes,
        ];
    }
}
