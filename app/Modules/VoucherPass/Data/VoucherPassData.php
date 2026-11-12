<?php

namespace App\Modules\VoucherPass\Data;

readonly class VoucherPassData
{
    /**
     * @param array $values
     * @param int $ownerIdentityId
     * @param int $targetIdentityId
     * @param int[] $typeIds
     */
    public function __construct(
        public array $values,
        public int $ownerIdentityId,
        public int $targetIdentityId,
        public array $typeIds,
    ) {
    }
}
