<?php

namespace App\Models\Data;

use JsonSerializable;

readonly class FundRequestProfileRecordPreview implements JsonSerializable
{
    /**
     * @param string $recordTypeKey
     * @param string $recordTypeName
     * @param string|null $currentValue
     * @param string|null $applicationValue
     * @param 'add'|'update'|'unchanged'|'not_copied' $action
     */
    public function __construct(
        public string $recordTypeKey,
        public string $recordTypeName,
        public ?string $currentValue,
        public ?string $applicationValue,
        public string $action,
    ) {
    }

    /**
     * @return array<string, string|null>
     */
    public function jsonSerialize(): array
    {
        return [
            'record_type_key' => $this->recordTypeKey,
            'record_type_name' => $this->recordTypeName,
            'current_value' => $this->currentValue,
            'application_value' => $this->applicationValue,
            'action' => $this->action,
        ];
    }
}
