<?php

namespace App\Exports;

use App\Models\Fund;
use App\Models\Organization;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;

class FundsExport implements FromCollection, WithHeadings, WithEvents
{
    use Exportable, RegistersEventListeners;

    protected $request;
    protected $totals;
    protected $data;
    protected $is_detailed;
    protected $headers;

    /**
     * VoucherTransactionsSponsorExport constructor.
     * @param Request $request
     * @param Organization $organization
     */
    public function __construct(
        Request $request,
        Organization $organization
    ) {
        $this->request = $request;

        $export_data = Fund::exportTransform(
            $this->request,
            $organization
        );

        $this->totals = $export_data['totals'];
        $this->data   = $export_data['data'];
        $this->is_detailed = $this->request->get('detailed', false);
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
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

    /**
     * @return array
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $lastRow = $event->sheet->getHighestRow() + 1;

                $event->sheet->setCellValue('A'. $lastRow, 'Total');
                $event->sheet->setCellValue('B'. $lastRow, $this->totals['total_budget']);

                if (!$this->is_detailed) {
                    $event->sheet->setCellValue('C'. $lastRow, $this->totals['total_budget_left']);
                    $event->sheet->setCellValue('D'. $lastRow, $this->totals['total_budget_used']);
                    $event->sheet->setCellValue('E'. $lastRow, $this->totals['total_transaction_costs']);
                } else {
                    $event->sheet->setCellValue('C'. $lastRow, $this->totals['total_active_vouchers']);
                    $event->sheet->setCellValue('D'. $lastRow, $this->totals['total_inactive_vouchers']);
                    $event->sheet->setCellValue('E'. $lastRow, $this->totals['total_budget_used']);
                    $event->sheet->setCellValue('F'. $lastRow, $this->totals['total_budget_left']);
                }
            }
        ];
    }
}
