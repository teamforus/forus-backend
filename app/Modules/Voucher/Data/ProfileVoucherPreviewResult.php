<?php

namespace App\Modules\Voucher\Data;

use JsonSerializable;

readonly class ProfileVoucherPreviewResult implements JsonSerializable
{
    /**
     * @param int $fundId
     * @param string $fundName
     * @param int $profileId
     * @param int $identityId
     * @param string|null $profileName
     * @param string|null $clientNumber
     * @param 'eligible'|'skipped'|'failed' $status
     * @param string $message
     * @param array<int, string> $errors
     * @param string|null $diagnosticId
     * @param int $voucherCount
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
        public int $voucherCount,
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
            'voucher_count' => $this->voucherCount,
        ];
    }
}
