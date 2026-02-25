<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use App\Models\Reseller;

class ResellerPaymentTemplateExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return Reseller::regular()->select('id', 'name', 'due_amount')->orderBy('name')->get();
    }

    public function headings(): array
    {
        return [
            'Reseller ID (Do Not Change)',
            'Reseller Name',
            'Current Due',
            'Payment Amount',
            'Payment Method (cash/bank/other)',
            'Reference',
            'Date (YYYY-MM-DD)'
        ];
    }

    public function map($reseller): array
    {
        return [
            $reseller->id,
            $reseller->name,
            $reseller->due_amount,
            '', // Payment Amount Empty
            'cash', // Default Method
            '', // Reference Empty
            date('Y-m-d'), // Default Date
        ];
    }
}
