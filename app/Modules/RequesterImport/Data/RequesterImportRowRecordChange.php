<?php

namespace App\Modules\RequesterImport\Data;

use JsonSerializable;

readonly class RequesterImportRowRecordChange implements JsonSerializable
{
    /**
     * @param string $recordTypeKey
     * @param string|null $previousValue
     * @param string $value
     */
    public function __construct(
        public string $recordTypeKey,
        public ?string $previousValue,
        public string $value,
    ) {
    }

    /**
     * @return array<string, string|null>
     */
    public function jsonSerialize(): array
    {
        return [
            'record_type_key' => $this->recordTypeKey,
            'previous_value' => $this->previousValue,
            'value' => $this->value,
            'action' => match (true) {
                $this->previousValue === null => 'add',
                $this->value === '' => 'clear',
                default => 'update',
            },
        ];
    }
}
