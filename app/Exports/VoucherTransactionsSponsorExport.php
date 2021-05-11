<?php

namespace App\Exports;

use App\Models\Fund;
use App\Models\Organization;
use App\Models\VoucherTransaction;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Collection;

/**
 * Class VoucherTransactionsSponsorExport
 * @package App\Exports
 */
class VoucherTransactionsSponsorExport implements FromCollection, WithHeadings
{
    protected $request;
    protected $data;
    protected $headers;

    /**
     * VoucherTransactionsSponsorExport constructor.
     * @param Request $request
     * @param Organization $organization
     * @param Fund|null $fund
     * @param Organization|null $provider
     */
    public function __construct(
        Request $request,
        Organization $organization,
        Fund $fund = null,
        Organization $provider = null
    ) {
        $this->request = $request;

        $this->data = VoucherTransaction::exportSponsor(
            $this->request,
            $organization,
            $fund,
            $provider
        );
    }

    /**
    * @return Collection
    */
    public function collection(): Collection
    {
        return $this->data;
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return $this->data->map(function ($row) {
            return array_keys($row);
        })->flatten()->unique()->toArray();
    }
}
