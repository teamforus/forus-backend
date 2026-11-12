<?php

namespace App\Modules\RequesterImport\Data;

use JsonSerializable;

readonly class RequesterImportProfileRecordChange implements JsonSerializable
{
    /**
     * @param string $recordTypeKey
     * @param string|null $currentValue
     * @param string $value
     * @param 'add'|'update'|'clear'|'confirm_source' $action
     */
    public function __construct(
        public string $recordTypeKey,
        public ?string $currentValue,
        public string $value,
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
            'current_value' => $this->currentValue,
            'value' => $this->value,
            'action' => $this->action,
        ];
    }
}
