<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use App\Models\Category;
use App\Models\Unit;

class ProductTemplateExport implements FromCollection, WithHeadings, WithTitle, WithStyles, WithEvents
{
    public function collection()
    {
        // Return Sample Data
        return collect([
            [
                'Example Product',  // Name
                'Electronics',      // Category Name (Must match exactly)
                'Headphones',       // Sub Category Name (Optional)
                'Wireless Bluetooth Headphones', // Description
                'Bottle',              // Unit Name (Must match exactly, e.g. Bottle, Box, Piece)
                '1',                // Unit Value (e.g. 500, 1.5)
                '2500.00',          // Selling Price
                '3000.00',          // Limit Price
                '5',                // Alert Quantity
                'https://example.com/image.jpg' // Image URL
            ]
        ]);
    }

    public function headings(): array
    {
        return [
            'Product Name*',
            'Category*',
            'Sub Category',
            'Description',
            'Unit Name*',
            'Unit Value',
            'Selling Price*',
            'Limit Price',
            'Alert Quantity',
            'Image URL'
        ];
    }

    public function title(): string
    {
        return 'Product Import Template';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
    
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet;
                
                // 1. Category Dropdown (Column B)
                $categories = Category::pluck('name')->toArray();
                if(!empty($categories)) {
                    $catValidation = $sheet->getCell('B2')->getDataValidation();
                    $catValidation->setType(DataValidation::TYPE_LIST);
                    $catValidation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                    $catValidation->setAllowBlank(false);
                    $catValidation->setShowInputMessage(true);
                    $catValidation->setShowErrorMessage(true);
                    $catValidation->setShowDropDown(true);
                    $catValidation->setFormula1('"' . implode(',', $categories) . '"');
                    
                    // Apply to 100 rows
                    for ($i = 2; $i <= 100; $i++) {
                        $sheet->getCell("B$i")->setDataValidation(clone $catValidation);
                    }
                }

                // 2. Sub Category Dropdown (Column C)
                $subCategories = \App\Models\SubCategory::pluck('name')->toArray();
                if(!empty($subCategories)) {
                    $subCatValidation = $sheet->getCell('C2')->getDataValidation();
                    $subCatValidation->setType(DataValidation::TYPE_LIST);
                    $subCatValidation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                    $subCatValidation->setAllowBlank(true);
                    $subCatValidation->setShowDropDown(true);
                    $subCatValidation->setFormula1('"' . implode(',', $subCategories) . '"');
                    
                     for ($i = 2; $i <= 100; $i++) {
                        $sheet->getCell("C$i")->setDataValidation(clone $subCatValidation);
                    }
                }

                // 3. Unit Dropdown (Column E)
                $units = Unit::pluck('name')->toArray();
                if(!empty($units)) {
                    $unitValidation = $sheet->getCell('E2')->getDataValidation();
                    $unitValidation->setType(DataValidation::TYPE_LIST);
                    $unitValidation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                    $unitValidation->setAllowBlank(false);
                    $unitValidation->setShowDropDown(true);
                    $unitValidation->setFormula1('"' . implode(',', $units) . '"');
                    
                     for ($i = 2; $i <= 100; $i++) {
                        $sheet->getCell("E$i")->setDataValidation(clone $unitValidation);
                    }
                }
            },
        ];
    }
}
