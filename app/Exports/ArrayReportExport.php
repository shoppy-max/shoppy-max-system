<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class ArrayReportExport implements FromArray, ShouldAutoSize, WithHeadings, WithTitle
{
    public function __construct(
        private readonly array $headings,
        private readonly array $rows,
        private readonly string $title
    ) {
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function title(): string
    {
        return mb_substr(preg_replace('/[\\\\\\/\\?\\*\\[\\]:]/', '', $this->title) ?: 'Report', 0, 31);
    }
}
