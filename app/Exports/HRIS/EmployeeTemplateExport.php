<?php

namespace App\Exports\HRIS;

use App\Models\Hris\Department;
use App\Models\Hris\Position;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

class EmployeeTemplateExport implements WithHeadings, WithTitle, WithEvents
{
    public function title(): string
    {
        return 'Employees';
    }

    public function headings(): array
    {
        return [
            'First Name',
            'Middle Name',
            'Last Name',
            'Date of Birth (YYYY-MM-DD)',
            'Gender',
            'Marital Status',
            'Personal Email',
            'Nationality',
            'National ID',
            'Passport Number',
            'Work Email',
            'Department',
            'Position',
            'Manager Email',
            'Employment Type',
            'Employment Status',
            'Hire Date (YYYY-MM-DD)',
            'Probation End Date (YYYY-MM-DD)',
            'Probation Status',
            'Notice Period (Days)',
            'Work Location',
            'Shift',
            'Work Schedule',
            'Remote Work Eligible (Yes/No)',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;

                // Fetch dynamic data
                $departments = Department::pluck('name')->toArray();
                $positions = Position::pluck('title')->toArray();

                // Static options
                $genders = ['Male', 'Female', 'Other'];
                $maritalStatuses = ['Single', 'Married', 'Divorced', 'Widowed'];
                $employmentTypes = ['Full-time', 'Part-time', 'Contract', 'Intern'];
                $employmentStatuses = ['Active', 'On Leave', 'Suspended'];
                $probationStatuses = ['Pending', 'Passed', 'Failed', 'Extended'];
                $shifts = ['Day', 'Night', 'Rotating'];
                $yesNo = ['Yes', 'No'];

                // Apply validations for 1000 rows
                $rowCount = 1000;

                $this->applyValidation($sheet, 'E', $genders, $rowCount); // Gender
                $this->applyValidation($sheet, 'F', $maritalStatuses, $rowCount); // Marital Status
                $this->applyValidation($sheet, 'L', $departments, $rowCount); // Department
                $this->applyValidation($sheet, 'M', $positions, $rowCount); // Position
                $this->applyValidation($sheet, 'O', $employmentTypes, $rowCount); // Type
                $this->applyValidation($sheet, 'P', $employmentStatuses, $rowCount); // Status
                $this->applyValidation($sheet, 'S', $probationStatuses, $rowCount); // Probation Status
                $this->applyValidation($sheet, 'V', $shifts, $rowCount); // Shift
                $this->applyValidation($sheet, 'X', $yesNo, $rowCount); // Remote Work
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
