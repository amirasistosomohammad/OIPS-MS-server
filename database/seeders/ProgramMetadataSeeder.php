<?php

namespace Database\Seeders;

use App\Models\Program;
use App\Models\ProgramFieldTemplate;
use App\Models\ProgramStatusOption;
use Illuminate\Database\Seeder;

class ProgramMetadataSeeder extends Seeder
{
    public function run(): void
    {
        $programs = [
            ['code' => 'SESP', 'name' => 'Skills for Employment Scholarship Program', 'type' => 'Educational and Training', 'interval' => 6],
            ['code' => 'SUP', 'name' => "Seafarer's Upgrading Program", 'type' => 'Educational and Training', 'interval' => 6],
            ['code' => 'IT', 'name' => 'Information Technology Program', 'type' => 'Educational and Training', 'interval' => 6],
            ['code' => 'EDSP', 'name' => 'Educational Development Scholarship Program', 'type' => 'Degree Granting', 'interval' => 6],
            ['code' => 'ODSP', 'name' => 'OFW Dependent Scholarship Program', 'type' => 'Degree Granting', 'interval' => 6],
            ['code' => 'ELAP', 'name' => 'Education and Livelihood Assistance Program', 'type' => 'Degree Granting', 'interval' => 6],
            ['code' => 'TAP', 'name' => 'Tuloy Aral Program', 'type' => 'Degree Granting', 'interval' => 6],
            ['code' => 'CMWSP', 'name' => 'Congressional Migrant Workers Scholarship Program', 'type' => 'Degree Granting', 'interval' => 6],
            ['code' => 'BPBH', 'name' => 'Balik-Pinas Hanapbuhay Program', 'type' => 'Reintegration', 'interval' => 12],
            ['code' => 'SWC', 'name' => 'Social Welfare Services - Welfare Case Management', 'type' => 'Labor Force and Welfare Services', 'interval' => 1],
            ['code' => 'PDOS', 'name' => 'Pre-Departure Orientation Seminar', 'type' => 'Pre-Departure Education Program', 'interval' => 0],
            ['code' => 'LCF', 'name' => 'Language, Culture and Familiarization', 'type' => 'Pre-Departure Education Program', 'interval' => 0],
        ];

        foreach ($programs as $programData) {
            $program = Program::query()->updateOrCreate(
                ['program_code' => $programData['code']],
                [
                    'program_name' => $programData['name'],
                    'program_type' => $programData['type'],
                    'update_mode' => $programData['interval'] > 0 ? 'interval' : 'event-driven',
                    'update_interval_months' => $programData['interval'] > 0 ? $programData['interval'] : null,
                    'is_active' => true,
                ],
            );

            $this->seedStatuses($program);
            $this->seedFieldTemplates($program);
        }
    }

    private function seedStatuses(Program $program): void
    {
        $statusSets = [
            'SWC' => ['ON_GOING' => 'On-going', 'CASE_CLOSED' => 'Case Closed'],
            'PDOS' => ['DONE' => 'Done', 'WITHDRAW' => 'Withdraw'],
            'LCF' => ['DONE' => 'Done', 'WITHDRAW' => 'Withdraw'],
            'BPBH' => ['OPERATIONAL' => 'Operational', 'NON_OPERATIONAL' => 'Non Operational'],
        ];

        $defaultStatuses = ['GRADUATED' => 'Graduated', 'MAINTAINED' => 'Maintained', 'SUSPENDED' => 'Suspended'];
        $statuses = $statusSets[$program->program_code] ?? $defaultStatuses;
        $statusCodes = array_keys($statuses);

        ProgramStatusOption::query()
            ->where('program_id', $program->id)
            ->whereNotIn('status_code', $statusCodes)
            ->update(['is_active' => false]);

        $order = 1;
        foreach ($statuses as $code => $label) {
            ProgramStatusOption::query()->updateOrCreate(
                ['program_id' => $program->id, 'status_code' => $code],
                ['status_label' => $label, 'display_order' => $order++, 'is_active' => true],
            );
        }
    }

    private function seedFieldTemplates(Program $program): void
    {
        $fieldSets = [
            'SESP' => [
                'input' => [
                    ['key' => 'school_year', 'label' => 'School Year'],
                    ['key' => 'school', 'label' => 'School'],
                    ['key' => 'course', 'label' => 'Course'],
                    ['key' => 'year_level', 'label' => 'Year Level'],
                ],
                'update' => [
                    ['key' => 'remarks', 'label' => 'Remarks', 'type' => 'textarea', 'required' => true],
                    ['key' => 'amount_received', 'label' => 'Amount Received', 'type' => 'number'],
                ],
            ],
            'SUP' => [
                'input' => [
                    ['key' => 'school_year', 'label' => 'School Year'],
                    ['key' => 'school', 'label' => 'School'],
                    ['key' => 'course', 'label' => 'Course'],
                    ['key' => 'year_level', 'label' => 'Year Level'],
                ],
                'update' => [
                    ['key' => 'remarks', 'label' => 'Remarks', 'type' => 'textarea', 'required' => true],
                    ['key' => 'amount_received', 'label' => 'Amount Received', 'type' => 'number'],
                ],
            ],
            'IT' => [
                'input' => [
                    ['key' => 'batch', 'label' => 'Batch'],
                    ['key' => 'date_enrolled', 'label' => 'Date Enrolled', 'type' => 'date'],
                    ['key' => 'course', 'label' => 'Course'],
                    ['key' => 'course_duration', 'label' => 'Course Duration'],
                ],
                'update' => [
                    ['key' => 'remarks', 'label' => 'Remarks', 'type' => 'textarea', 'required' => true],
                    ['key' => 'certificate', 'label' => 'Certificate'],
                ],
            ],
            'EDSP' => [
                'input' => [
                    ['key' => 'batch', 'label' => 'Batch'],
                    ['key' => 'program_type', 'label' => 'Program Type'],
                    ['key' => 'school', 'label' => 'School'],
                    ['key' => 'year_level_course', 'label' => 'Year Level/Course'],
                ],
                'update' => [
                    ['key' => 'grade_info', 'label' => 'Every Sem Grade', 'type' => 'textarea'],
                    ['key' => 'remarks', 'label' => 'Remarks', 'type' => 'textarea', 'required' => true],
                    ['key' => 'amount_received', 'label' => 'Amount Received Every Sem', 'type' => 'number'],
                ],
            ],
            'ODSP' => [
                'input' => [
                    ['key' => 'batch', 'label' => 'Batch'],
                    ['key' => 'program_type', 'label' => 'Program Type'],
                    ['key' => 'school', 'label' => 'School'],
                    ['key' => 'year_level_course', 'label' => 'Year Level/Course'],
                ],
                'update' => [
                    ['key' => 'grade_info', 'label' => 'Every Sem Grade', 'type' => 'textarea'],
                    ['key' => 'remarks', 'label' => 'Remarks', 'type' => 'textarea', 'required' => true],
                    ['key' => 'amount_received', 'label' => 'Amount Received Every Sem', 'type' => 'number'],
                ],
            ],
            'ELAP' => [
                'input' => [
                    ['key' => 'batch', 'label' => 'Batch'],
                    ['key' => 'program_type', 'label' => 'Program Type'],
                    ['key' => 'school', 'label' => 'School'],
                    ['key' => 'year_level_course', 'label' => 'Year Level/Course'],
                ],
                'update' => [
                    ['key' => 'grade_info', 'label' => 'Every Sem Grade', 'type' => 'textarea'],
                    ['key' => 'remarks', 'label' => 'Remarks', 'type' => 'textarea', 'required' => true],
                    ['key' => 'amount_received', 'label' => 'Amount Received Every Sem', 'type' => 'number'],
                ],
            ],
            'TAP' => [
                'input' => [
                    ['key' => 'batch', 'label' => 'Batch'],
                    ['key' => 'program_type', 'label' => 'Program Type'],
                    ['key' => 'school', 'label' => 'School'],
                    ['key' => 'year_level_course', 'label' => 'Year Level/Course'],
                ],
                'update' => [
                    ['key' => 'grade_info', 'label' => 'Every Sem Grade', 'type' => 'textarea'],
                    ['key' => 'remarks', 'label' => 'Remarks', 'type' => 'textarea', 'required' => true],
                    ['key' => 'amount_received', 'label' => 'Amount Received Every Sem', 'type' => 'number'],
                ],
            ],
            'CMWSP' => [
                'input' => [
                    ['key' => 'batch', 'label' => 'Batch'],
                    ['key' => 'program_type', 'label' => 'Program Type'],
                    ['key' => 'school', 'label' => 'School'],
                    ['key' => 'year_level_course', 'label' => 'Year Level/Course'],
                ],
                'update' => [
                    ['key' => 'grade_info', 'label' => 'Every Sem Grade', 'type' => 'textarea'],
                    ['key' => 'remarks', 'label' => 'Remarks', 'type' => 'textarea', 'required' => true],
                    ['key' => 'amount_received', 'label' => 'Amount Received Every Sem', 'type' => 'number'],
                ],
            ],
            'BPBH' => [
                'input' => [
                    ['key' => 'date_cheque_released', 'label' => 'Date Cheque Released', 'type' => 'date'],
                    ['key' => 'business_status', 'label' => 'Operational Status'],
                    ['key' => 'last_monitored_at', 'label' => 'Last Monitored (Called)', 'type' => 'date'],
                ],
                'update' => [
                    ['key' => 'date_cheque_released', 'label' => 'Date Cheque Released', 'type' => 'date'],
                    ['key' => 'business_status', 'label' => 'Operational Status'],
                    ['key' => 'last_monitored_at', 'label' => 'Last Monitored (Called)', 'type' => 'date'],
                    ['key' => 'remarks', 'label' => 'Remarks', 'type' => 'textarea', 'required' => true],
                ],
            ],
            'SWC' => [
                'input' => [
                    ['key' => 'date_intake', 'label' => 'Date / Endorsed Intake', 'type' => 'date'],
                    ['key' => 'address', 'label' => 'Address'],
                    ['key' => 'employer_contact', 'label' => 'Name of Employer / Contact Number'],
                    ['key' => 'next_of_kin', 'label' => 'Next-of-Kin'],
                ],
                'update' => [
                    ['key' => 'nature_of_case', 'label' => 'Nature of Case'],
                    ['key' => 'assistance_needed', 'label' => 'Assistance Needed', 'type' => 'textarea'],
                    ['key' => 'action_taken', 'label' => 'Action Taken', 'type' => 'textarea'],
                ],
            ],
            'PDOS' => [
                'input' => [
                    ['key' => 'date_oriented', 'label' => 'Date Oriented', 'type' => 'date'],
                    ['key' => 'jobsite_bound_for', 'label' => 'Jobsite (Bound For)'],
                    ['key' => 'agency', 'label' => 'Agency'],
                    ['key' => 'service_provider', 'label' => 'Service Provider'],
                ],
                'update' => [
                    ['key' => 'remarks', 'label' => 'Remarks', 'type' => 'textarea'],
                ],
            ],
            'LCF' => [
                'input' => [
                    ['key' => 'batch', 'label' => 'Batch'],
                    ['key' => 'agency_hire', 'label' => 'Agency Hire'],
                    ['key' => 'module', 'label' => 'Module'],
                    ['key' => 'course_duration', 'label' => 'Course Duration'],
                ],
                'update' => [
                    ['key' => 'remarks', 'label' => 'Remarks', 'type' => 'textarea'],
                ],
            ],
        ];

        $fallback = [
            'input' => [
                ['key' => 'batch', 'label' => 'Batch'],
                ['key' => 'date_enrolled', 'label' => 'Date Enrolled', 'type' => 'date'],
                ['key' => 'category', 'label' => 'Category'],
            ],
            'update' => [
                ['key' => 'remarks', 'label' => 'Remarks', 'type' => 'textarea', 'required' => true],
            ],
        ];

        $selected = $fieldSets[$program->program_code] ?? $fallback;
        $inputFields = $selected['input'];
        $updateFields = $selected['update'];
        $inputKeys = array_column($inputFields, 'key');
        $updateKeys = array_column($updateFields, 'key');

        ProgramFieldTemplate::query()
            ->where('program_id', $program->id)
            ->where('field_scope', 'input')
            ->whereNotIn('field_key', $inputKeys)
            ->update(['is_active' => false]);

        ProgramFieldTemplate::query()
            ->where('program_id', $program->id)
            ->where('field_scope', 'update')
            ->whereNotIn('field_key', $updateKeys)
            ->update(['is_active' => false]);

        $order = 1;
        foreach ($inputFields as $field) {
            ProgramFieldTemplate::query()->updateOrCreate(
                ['program_id' => $program->id, 'field_key' => $field['key'], 'field_scope' => 'input'],
                [
                    'field_label' => $field['label'],
                    'field_type' => $field['type'] ?? 'text',
                    'is_required' => $field['required'] ?? false,
                    'display_order' => $order++,
                    'is_active' => true,
                ],
            );
        }

        $order = 1;
        foreach ($updateFields as $field) {
            ProgramFieldTemplate::query()->updateOrCreate(
                ['program_id' => $program->id, 'field_key' => $field['key'], 'field_scope' => 'update'],
                [
                    'field_label' => $field['label'],
                    'field_type' => $field['type'] ?? 'text',
                    'is_required' => $field['required'] ?? false,
                    'display_order' => $order++,
                    'is_active' => true,
                ],
            );
        }
    }
}
