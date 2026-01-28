<?php

namespace App\Http\Resources\Sponsor;

use App\Http\Resources\BaseJsonResource;
use App\Models\FundRequest;
use App\Models\ProfileBankAccount;
use App\Models\Reimbursement;
use App\Models\VoucherTransaction;
use Illuminate\Http\Request;

/**
 * @property FundRequest|ProfileBankAccount|Reimbursement|VoucherTransaction $resource
 */
class SponsorPayoutBankAccountResource extends BaseJsonResource
{
    /**
     * @var array
     */
    public const array LOAD = [];

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $resource = $this->resource;

        // Handle FundRequest model
        if ($resource instanceof FundRequest) {
            return $this->transformBankAccount(
                $resource->id,
                $resource->getIban(false),
                $resource->getIbanName(false),
                'fund_request',
                $resource->only(['created_at', 'updated_at']),
            );
        }

        // Handle ProfileBankAccount model
        if ($resource instanceof ProfileBankAccount) {
            return $this->transformBankAccount(
                $resource->id,
                $resource->iban,
                $resource->name,
                'profile_bank_account',
                $resource->only(['created_at', 'updated_at']),
            );
        }

        // Handle Reimbursement model
        if ($resource instanceof Reimbursement) {
            return $this->transformBankAccount(
                $resource->id,
                $resource->iban,
                $resource->iban_name,
                'reimbursement',
                $resource->only(['created_at', 'updated_at']),
            );
        }

        // Handle VoucherTransaction model
        if ($resource instanceof VoucherTransaction) {
            return $this->transformBankAccount(
                $resource->id,
                $resource->target_iban,
                $resource->target_name,
                'payout',
                $resource->only(['created_at', 'updated_at']),
            );
        }

        return [];
    }

    /**
     * Transform bank account data into standardized array format.
     *
     * @param int $id
     * @param string $iban
     * @param string $ibanName
     * @param string $type
     * @param array $timestamps
     * @return array
     */
    private function transformBankAccount(int $id, string $iban, string $ibanName, string $type, array $timestamps): array
    {
        return [
            'iban' => $iban,
            'iban_name' => $ibanName,
            'type' => $type,
            'type_id' => $id,
            ...$this->makeTimestamps($timestamps),
        ];
    }
}
