<?php

namespace App\Modules\Voucher\Data;

use JsonSerializable;

readonly class ProfileVoucherAwardResult implements JsonSerializable
{
    /**
     * @param int $fundId
     * @param string $fundName
     * @param int $profileId
     * @param int $identityId
     * @param string|null $profileName
     * @param string|null $clientNumber
     * @param 'created'|'skipped'|'failed' $status
     * @param string $message
     * @param array<int, string> $errors
     * @param string|null $diagnosticId
     * @param array<int, int> $voucherIds
     */
    public function __construct(
        public int $fundId,
        public string $fundName,
        public int $profileId,
        public int $identityId,
        public ?string $profileName,
        public ?string $clientNumber,
        public string $status,
        public string $message,
        public array $errors,
        public ?string $diagnosticId,
        public array $voucherIds,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'fund_id' => $this->fundId,
            'fund_name' => $this->fundName,
            'profile_id' => $this->profileId,
            'identity_id' => $this->identityId,
            'profile_name' => $this->profileName,
            'client_number' => $this->clientNumber,
            'status' => $this->status,
            'message' => $this->message,
            'errors' => $this->errors,
            'diagnostic_id' => $this->diagnosticId,
            'voucher_ids' => $this->voucherIds,
        ];
    }
}
