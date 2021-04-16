<?php

namespace App\Exports;

use App\Models\Fund;
use App\Models\Organization;
use App\Models\VoucherTransaction;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Events\BeforeExport;

class FundsExport implements FromCollection, WithHeadings
{
    protected $request;
    protected $totals;
    protected $data;
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
        $styleTitulos = [
            'font' => [
                'bold' => true,
                'size' => 12
            ]
        ];

        return [
            BeforeExport::class => function(BeforeExport $event) {
                $event->writer->getProperties()->setCreator('Sistema de alquileres');
            },
            AfterSheet::class => function(AfterSheet $event) use ($styleTitulos){
                $event->sheet->getStyle("A1:G1")->applyFromArray($styleTitulos);
                $event->sheet->setCellValue('A'. ($event->sheet->getHighestRow()+1), "Total");
            }
        ];
    }
}
