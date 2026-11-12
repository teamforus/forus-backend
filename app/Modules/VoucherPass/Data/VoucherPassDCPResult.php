<?php

namespace App\Modules\VoucherPass\Data;

use App\Modules\VoucherPass\Enums\VoucherPassDCPStatus;
use JsonSerializable;

readonly class VoucherPassDCPResult implements JsonSerializable
{
    /**
     * @param int $id
     * @param string $code
     * @param int|null $orderId
     * @param VoucherPassDCPStatus $status
     * @param string $message
     * @param VoucherPassChange[] $changes
     * @param string[] $errors
     * @param string|null $diagnosticId
     */
    public function __construct(
        public int $id,
        public string $code,
        public ?int $orderId,
        public VoucherPassDCPStatus $status,
        public string $message,
        public array $changes = [],
        public array $errors = [],
        public ?string $diagnosticId = null,
    ) {
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'order_id' => $this->orderId,
            'status' => $this->status->value,
            'message' => $this->message,
            'changes' => $this->changes,
            'errors' => $this->errors,
            'diagnostic_id' => $this->diagnosticId,
        ];
    }
}
