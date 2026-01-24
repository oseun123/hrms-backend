<?php

namespace App\Exports\HRIS;

use App\Models\Hris\Department;
use App\Models\Hris\Position;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use Illuminate\Support\Collection;

class EmployeeTestDataExport implements FromCollection, WithHeadings, WithTitle, WithEvents
{
    public function title(): string
    {
        return 'Employees';
    }

    public function collection()
    {
        return new Collection([
            [
                'Alice',
                '',
                'Johnson',
                '1990-05-15',
                'Female',
                'Single',
                'alice.test@example.com',
                'American',
                'ID123',
                'PASS123',
                'alice.j@hrms.local',
                'Information Technology',
                'Software Developer',
                '',
                'Full-time',
                'Active',
                '2023-01-10',
                '2023-07-10',
                'Passed',
                '30',
                'Head Office',
                'Day',
                '9-5',
                'Yes'
            ],
            [
                'Bob',
                '',
                'Space',
                '1985-11-20',
                'Male',
                'Married',
                'bob.space@example.com',
                'British',
                'ID456',
                'PASS456',
                'bob.s@hrms.local',
                'Deep Space Exploration', // New Dept
                'Chief Astronaut', // New Position
                'alice.j@hrms.local',
                'Full-time',
                'Active',
                '2024-01-01',
                '2024-07-01',
                'Pending',
                '60',
                'Mars Station',
                'Rotating',
                '24/7',
                'No'
            ],
            [
                'Duplicate',
                '',
                'Email',
                '1992-03-10',
                'Other',
                'Single',
                'alice.test@example.com', // Duplicate email
                'Canadian',
                'ID789',
                'PASS789',
                'dup@hrms.local',
                'Support',
                'Help Desk',
                '',
                'Contract',
                'Active',
                '2024-02-01',
                '',
                '',
                '15',
                'Remote',
                'Day',
                'Flex',
                'Yes'
            ],
            [
                'Invalid',
                '',
                'Date',
                '2024-15-99', // Invalid date
                'Male',
                'Single',
                'invalid.date@example.com',
                'Nigerian',
                'ID000',
                'PASS000',
                'inv@hrms.local',
                'HR',
                'Recruiter',
                '',
                'Intern',
                'Active',
                'Not A Date', // Invalid date
                '',
                '',
                '0',
                'Office',
                'Day',
                'Shift A',
                'No'
            ],
        ]);
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

                // Fetch dynamic data for dropdowns
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

                $this->applyValidation($sheet, 'E', $genders, $rowCount);
                $this->applyValidation($sheet, 'F', $maritalStatuses, $rowCount);
                $this->applyValidation($sheet, 'L', $departments, $rowCount);
                $this->applyValidation($sheet, 'M', $positions, $rowCount);
                $this->applyValidation($sheet, 'O', $employmentTypes, $rowCount);
                $this->applyValidation($sheet, 'P', $employmentStatuses, $rowCount);
                $this->applyValidation($sheet, 'S', $probationStatuses, $rowCount);
                $this->applyValidation($sheet, 'V', $shifts, $rowCount);
                $this->applyValidation($sheet, 'X', $yesNo, $rowCount);
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
