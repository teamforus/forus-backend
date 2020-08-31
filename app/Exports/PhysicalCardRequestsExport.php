<?php

namespace App\Exports;

use App\Models\PhysicalCardRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PhysicalCardRequestsExport implements FromCollection, WithHeadings
{
    protected $data;
    protected $headers;

    public function __construct(
        $fund_id = null,
        $date = null
    ) {
        $this->data = $this->getRequests($fund_id, $date)->map(static function(
            PhysicalCardRequest $physicalCard
        ) {
            return array_combine([
                'ADRES', 'HUISNUMMER', 'HUISNR_TOEVOEGING', 'POSTCODE', 'PLAATS',
            ], $physicalCard->only([
                'address', 'house', 'house_addition', 'postcode', 'city',
            ]));
        });
    }

    /**
     * @param null $fund_id
     * @param null $date
     * @return Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    private function getRequests(
        $fund_id = null,
        $date = null
    ) {
        /** @var Builder $query */
        $query = PhysicalCardRequest::query();

        if ($fund_id) {
            $query->whereHas('voucher', static function(Builder $query) use ($fund_id) {
                $query->where('fund_id', $fund_id);
            });
        }

        if ($date) {
            $query->whereDate('created_at', $date);
        }

        return $query->get();
    }

    /**
    * @return \Illuminate\Support\Collection
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
        return $this->data->map(static function ($row) {
            return array_keys($row);
        })->flatten()->unique()->toArray();
    }
}
