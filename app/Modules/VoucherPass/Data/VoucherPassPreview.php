<?php

namespace App\Modules\VoucherPass\Data;

use JsonSerializable;

readonly class VoucherPassPreview implements JsonSerializable
{
    /**
     * @param int $index
     * @param int|null $id
     * @param string|null $code
     * @param 'create'|'update'|'unchanged' $action
     * @param array<int, VoucherPassChange> $changes
     */
    public function __construct(
        public int $index,
        public ?int $id,
        public ?string $code,
        public string $action,
        public array $changes,
    ) {
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [
            'index' => $this->index,
            'id' => $this->id,
            'code' => $this->code,
            'action' => $this->action,
            'changes' => $this->changes,
        ];
    }
}
