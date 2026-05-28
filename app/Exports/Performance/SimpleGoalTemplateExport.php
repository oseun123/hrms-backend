<?php

namespace App\Exports\Performance;

use App\Models\Performance\GoalAreaOfFocus;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use Illuminate\Support\Collection;

class SimpleGoalTemplateExport implements WithHeadings, WithTitle, WithEvents, FromCollection
{
    public function title(): string
    {
        return 'Simple Goals';
    }

    public function collection()
    {
        return new Collection([
            [
                'Increase Annual Revenue',
                'Overall revenue growth target for the fiscal year',
                'Finance',
                'simple',
                'Total Revenue Achieved',
                '$10,000,000',
                'USD',
                100
            ],
            [
                'Customer Satisfaction',
                'Improve NPS scores through better support',
                'Customer Success',
                'simple',
                'Net Promoter Score',
                '75',
                'Points',
                100
            ],
        ]);
    }

    public function headings(): array
    {
        return [
            'Goal Title',
            'Description',
            'Area of Focus',
            'Structure Type',
            'Measure Description',
            'Target Description',
            'Unit of Measure (UOM)',
            'Weightage (%)',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;
                $areas = GoalAreaOfFocus::pluck('name')->toArray();
                $rowCount = 1000;

                $this->applyValidation($sheet, 'C', $areas, $rowCount); // Area of Focus
                $this->applyValidation($sheet, 'D', ['simple', 'complex'], $rowCount); // Structure Type

                $sheet->getColumnDimension('A')->setWidth(30);
                $sheet->getColumnDimension('B')->setWidth(40);
                $sheet->getColumnDimension('C')->setWidth(20);
                $sheet->getColumnDimension('D')->setWidth(15);
                $sheet->getColumnDimension('E')->setWidth(30);
                $sheet->getColumnDimension('F')->setWidth(20);
                $sheet->getColumnDimension('G')->setWidth(15);
                $sheet->getColumnDimension('H')->setWidth(15);

                // Header styling
                $sheet->getStyle('A1:H1')->getFont()->setBold(true);
            },
        ];
    }

    private function applyValidation($sheet, $column, $options, $rowCount)
    {
        if (empty($options)) return;
        $validation = $sheet->getCell("{$column}2")->getDataValidation();
        $validation->setType(DataValidation::TYPE_LIST);
        $validation->setErrorStyle(DataValidation::STYLE_STOP);
        $validation->setAllowBlank(true);
        $validation->setShowDropDown(true);
        $validation->setFormula1('"' . implode(',', $options) . '"');

        for ($i = 2; $i <= $rowCount; $i++) {
            $sheet->getCell("{$column}{$i}")->setDataValidation(clone $validation);
        }
    }
}
