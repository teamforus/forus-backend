<?php

namespace Tests\Traits;

use App\Models\Employee;
use App\Models\Organization;
use App\Models\Voucher;
use App\Models\VoucherTransaction;

trait MakesTestTransaction
{
    /**
     * @param Organization $organization
     * @param Voucher $voucher
     * @param array $voucherData
     * @return VoucherTransaction
     */
    protected function makeTestTransaction(Organization $organization, Voucher $voucher, array $voucherData = []): VoucherTransaction
    {
        /** @var Employee $employee */
        $employee = $organization->employees()->first();

        return $voucher->makeTransaction([
            'initiator' => VoucherTransaction::INITIATOR_SPONSOR,
            'employee_id' => $employee?->id,
            'branch_id' => $employee?->office?->branch_id,
            'branch_name' => $employee?->office?->branch_name,
            'branch_number' => $employee?->office?->branch_number,
            'organization_id' => $organization->id,
            ...$voucherData,
        ]);
    }
}