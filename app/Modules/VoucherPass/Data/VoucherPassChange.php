<?php

namespace App\Modules\VoucherPass\Data;

use JsonSerializable;

readonly class VoucherPassChange implements JsonSerializable
{
    /**
     * @param string $field
     * @param string|bool|array|null $previousValue
     * @param string|bool|array|null $value
     */
    public function __construct(
        public string $field,
        public string|bool|array|null $previousValue,
        public string|bool|array|null $value,
    ) {
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [
            'field' => $this->field,
            'previous_value' => $this->previousValue,
            'value' => $this->value,
            'action' => match (true) {
                in_array($this->previousValue, [null, '', []], true) => 'add',
                in_array($this->value, [null, '', []], true) => 'clear',
                default => 'update',
            },
        ];
    }
}
