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

class ComplexGoalTemplateExport implements WithHeadings, WithTitle, WithEvents, FromCollection
{
    public function title(): string
    {
        return 'Complex Goals';
    }

    public function collection()
    {
        return new Collection([
            // Goal 1: Regional Expansion (2 Objectives)
            [
                'Regional Expansion Strategy',
                'Comprehensive plan to enter North American markets',
                'Business Development',
                'complex',
                'Establish NY Headquarters',
                'Secure office space and hire initial 10 staff',
                'Office operational and staff onboarded',
                '100%',
                'Status',
                50
            ],
            [
                '', // Same goal
                '',
                '',
                '', // Same structure type
                '', // Same objective (Establish NY Headquarters)
                '',
                'Complete legal entity registration',
                'Certificate of Incorporation received',
                'Status',
                50
            ],
            [
                '', // Same goal
                '',
                '',
                '',
                'Marketing Launch',
                'Execute digital ad campaign in North America',
                'Generate 500 qualified leads',
                '500',
                'Leads',
                50
            ],
            // Goal 2: Product Modernization
            [
                'Product Modernization Q3',
                'Updating core systems to latest tech stack',
                'Engineering',
                'complex',
                'Legacy Migration',
                'Migrate 100% of user data to new database',
                'Zero data loss during migration',
                '100',
                '%',
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
            'Objective Title',
            'Objective Description',
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
                $sheet->getColumnDimension('F')->setWidth(30);
                $sheet->getColumnDimension('G')->setWidth(30);
                $sheet->getColumnDimension('H')->setWidth(25);
                $sheet->getColumnDimension('I')->setWidth(15);
                $sheet->getColumnDimension('J')->setWidth(15);

                // Header styling
                $sheet->getStyle('A1:J1')->getFont()->setBold(true);
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
