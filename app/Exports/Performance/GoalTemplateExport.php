<?php

namespace App\Exports\Performance;

use App\Models\Performance\GoalAreaOfFocus;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

class GoalTemplateExport implements WithHeadings, WithTitle, WithEvents
{
    public function title(): string
    {
        return 'Goal Templates';
    }

    public function headings(): array
    {
        return [
            'Goal Title',
            'Description',
            'Area of Focus',
            'Structure Type',
            'Objective Title (Complex only)',
            'Objective Description (Complex only)',
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

                // Fetch dynamic data
                $areas = GoalAreaOfFocus::pluck('name')->toArray();

                // Static options
                $types = ['simple', 'complex'];

                // Apply validations for 1000 rows
                $rowCount = 1000;

                $this->applyValidation($sheet, 'C', $areas, $rowCount); // Area of Focus
                $this->applyValidation($sheet, 'D', $types, $rowCount); // Structure Type

                // Set column widths
                $sheet->getColumnDimension('A')->setWidth(30);
                $sheet->getColumnDimension('B')->setWidth(30);
                $sheet->getColumnDimension('C')->setWidth(20);
                $sheet->getColumnDimension('D')->setWidth(15);
                $sheet->getColumnDimension('E')->setWidth(30);
                $sheet->getColumnDimension('F')->setWidth(30);
                $sheet->getColumnDimension('G')->setWidth(30);
                $sheet->getColumnDimension('H')->setWidth(20);
                $sheet->getColumnDimension('I')->setWidth(15);
                $sheet->getColumnDimension('J')->setWidth(15);
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
        $validation->setShowInputMessage(true);
        $validation->setShowErrorMessage(true);
        $validation->setShowDropDown(true);
        $validation->setErrorTitle('Input error');
        $validation->setError('Value is not in list.');
        $validation->setPromptTitle('Pick from list');
        $validation->setPrompt('Please pick a value from the drop-down list.');
        $validation->setFormula1('"' . implode(',', $options) . '"');

        for ($i = 3; $i <= $rowCount; $i++) {
            $sheet->getCell("{$column}{$i}")->setDataValidation(clone $validation);
        }
    }
}
