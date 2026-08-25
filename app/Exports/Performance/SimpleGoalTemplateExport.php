<?php

namespace App\Exports\Performance;

use App\Models\Performance\GoalAreaOfFocus;
use Illuminate\Support\Facades\Auth;
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
                'Revenue Target Achievement',
                'Achieve assigned revenue and business development targets for the performance cycle.',
                'Strategic Goals',
                'simple',
                'Closed-won revenue against assigned quota',
                'Meet or exceed 100% of assigned revenue quota',
                '%',
                25
            ],
            [
                'On-Time Project & Milestone Delivery',
                'Ensure all assigned projects, deliverables, and milestones are completed within agreed deadlines.',
                'Operational Excellence',
                'simple',
                'Milestones and deliverables completed on time',
                '95% on-time milestone completion rate',
                '%',
                20
            ],
            [
                'Quality Assurance & SLA Compliance',
                'Maintain high operational quality standards and adhere strictly to Service Level Agreements.',
                'Operational Excellence',
                'simple',
                'SLA adherence and operational error rate',
                'Zero critical SLA breaches and 98% uptime / compliance',
                '%',
                15
            ],
            [
                'Customer Satisfaction (CSAT)',
                'Deliver high-quality service and support to maintain outstanding customer satisfaction ratings.',
                'Customer Focus',
                'simple',
                'Average customer satisfaction feedback score',
                'Maintain an average CSAT rating of 90% or higher',
                '%',
                15
            ],
            [
                'Client Retention & Account Health',
                'Foster strong customer relationships and drive proactive client engagement and renewals.',
                'Customer Focus',
                'simple',
                'Client retention and proactive relationship reviews',
                'Achieve 90% client retention and conduct quarterly reviews',
                '%',
                10
            ],
            [
                'Process Optimization & Efficiency',
                'Identify inefficiencies and implement innovative process improvements or automation.',
                'Innovation & Growth',
                'simple',
                'Process optimization initiatives implemented',
                'Deliver at least 2 process improvement initiatives',
                'Initiatives',
                10
            ],
            [
                'Continuous Learning & Upskilling',
                'Expand professional capabilities through training, certifications, and internal knowledge sharing.',
                'Team Development',
                'simple',
                'Professional training completed and knowledge sessions hosted',
                'Complete 40 hours of training or host 2 knowledge-share sessions',
                'Hours',
                5
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
                $tenantId = Auth::user()?->tenant_id;
                
                $query = GoalAreaOfFocus::query();
                if ($tenantId) {
                    $query->where('tenant_id', $tenantId);
                }
                $areas = $query->pluck('name')->toArray();
                if (empty($areas)) {
                    $areas = ['Strategic Goals', 'Operational Excellence', 'Customer Focus', 'Innovation & Growth', 'Team Development'];
                }
                $rowCount = 1000;

                $this->applyValidation($sheet, 'C', $areas, $rowCount); // Area of Focus
                $this->applyValidation($sheet, 'D', ['simple'], $rowCount); // Structure Type

                $sheet->getColumnDimension('A')->setWidth(32);
                $sheet->getColumnDimension('B')->setWidth(40);
                $sheet->getColumnDimension('C')->setWidth(24);
                $sheet->getColumnDimension('D')->setWidth(16);
                $sheet->getColumnDimension('E')->setWidth(35);
                $sheet->getColumnDimension('F')->setWidth(30);
                $sheet->getColumnDimension('G')->setWidth(18);
                $sheet->getColumnDimension('H')->setWidth(16);

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
