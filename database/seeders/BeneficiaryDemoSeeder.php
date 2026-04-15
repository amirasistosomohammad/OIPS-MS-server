<?php

namespace Database\Seeders;

use App\Models\Beneficiary;
use App\Models\BeneficiaryNotification;
use App\Models\Program;
use App\Models\ProgramEnrollment;
use App\Models\ProgramStatusOption;
use App\Models\ProgramUpdateEntry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class BeneficiaryDemoSeeder extends Seeder
{
    public function run(): void
    {
        $programsByCode = Program::query()->get()->keyBy('program_code');

        $beneficiaries = [
            ['no' => 'OWWA-R9-2026-0001', 'last' => 'Balde', 'first' => 'Jefferson', 'sex' => 'Male', 'office' => 'RWO IX', 'ofw' => 'Balde, Romel'],
            ['no' => 'OWWA-R9-2026-0002', 'last' => 'Santos', 'first' => 'Maria', 'sex' => 'Female', 'office' => 'RWO IX', 'ofw' => 'Santos, Allan'],
            ['no' => 'OWWA-R9-2026-0003', 'last' => 'Dela Cruz', 'first' => 'Rico', 'sex' => 'Male', 'office' => 'RWO IX', 'ofw' => 'Dela Cruz, Nestor'],
            ['no' => 'OWWA-R9-2026-0004', 'last' => 'Torres', 'first' => 'May', 'sex' => 'Female', 'office' => 'RWO IX', 'ofw' => 'Torres, Mario'],
            ['no' => 'OWWA-R9-2026-0005', 'last' => 'Garcia', 'first' => 'Liza', 'sex' => 'Female', 'office' => 'RWO IX', 'ofw' => 'Garcia, Ben'],
            ['no' => 'OWWA-R9-2026-0006', 'last' => 'Lopez', 'first' => 'Carlo', 'sex' => 'Male', 'office' => 'RWO IX', 'ofw' => 'Lopez, Renato'],
            ['no' => 'OWWA-R9-2026-0007', 'last' => 'Navarro', 'first' => 'Angel', 'sex' => 'Female', 'office' => 'RWO IX', 'ofw' => 'Navarro, Carlos'],
            ['no' => 'OWWA-R9-2026-0008', 'last' => 'Ramos', 'first' => 'John', 'sex' => 'Male', 'office' => 'RWO IX', 'ofw' => 'Ramos, Tomas'],
            ['no' => 'OWWA-R9-2026-0009', 'last' => 'Fernandez', 'first' => 'Nina', 'sex' => 'Female', 'office' => 'RWO IX', 'ofw' => 'Fernandez, Pedro'],
            ['no' => 'OWWA-R9-2026-0010', 'last' => 'Reyes', 'first' => 'Kurt', 'sex' => 'Male', 'office' => 'RWO IX', 'ofw' => 'Reyes, Efren'],
            ['no' => 'OWWA-R9-2026-0011', 'last' => 'Aguirre', 'first' => 'Lourdes', 'sex' => 'Female', 'office' => 'RWO IX', 'ofw' => 'Aguirre, Danilo'],
            ['no' => 'OWWA-R9-2026-0012', 'last' => 'Mendoza', 'first' => 'Paolo', 'sex' => 'Male', 'office' => 'RWO IX', 'ofw' => 'Mendoza, Ariel'],
        ];

        $programAssignments = ['SESP', 'SUP', 'IT', 'EDSP', 'ODSP', 'ELAP', 'TAP', 'CMWSP', 'SWC', 'PDOS', 'LCF', 'SESP'];

        foreach ($beneficiaries as $index => $entry) {
            $beneficiary = Beneficiary::query()->updateOrCreate(
                ['beneficiary_no' => $entry['no']],
                [
                    'last_name' => $entry['last'],
                    'first_name' => $entry['first'],
                    'birthdate' => Carbon::now()->subYears(18 + ($index % 8))->subDays($index * 13)->toDateString(),
                    'sex' => $entry['sex'],
                    'civil_status' => 'Single',
                    'contact_number' => '0917'.str_pad((string) (3000000 + ($index * 8473)), 7, '0', STR_PAD_LEFT),
                    'email' => strtolower($entry['first'].'.'.$entry['last']).'@mail.test',
                    'address' => 'Purok '.($index + 1).', Zamboanga City',
                    'barangay' => 'Barangay '.(($index % 5) + 1),
                    'municipality' => 'Zamboanga City',
                    'province' => 'Zamboanga del Sur',
                    'field_office' => $entry['office'],
                    'ofw_name' => $entry['ofw'],
                    'relationship_to_ofw' => 'Child',
                    'category' => 'Education',
                    'jobsite' => 'Middle East',
                    'position' => 'Student',
                    'is_active' => true,
                ],
            );

            $programCode = $programAssignments[$index] ?? 'SESP';
            $program = $programsByCode->get($programCode);
            if (! $program) {
                continue;
            }

            $dateEnrolled = Carbon::now()->subMonths(6 + $index);
            $nextDue = $program->update_interval_months
                ? $dateEnrolled->copy()->addMonths((int) $program->update_interval_months)
                : null;

            $enrollment = ProgramEnrollment::query()->firstOrCreate(
                [
                    'beneficiary_id' => $beneficiary->id,
                    'program_id' => $program->id,
                    'batch' => 'BATCH-2026-'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
                ],
                [
                    'date_enrolled' => $dateEnrolled->toDateString(),
                    'enrollment_status' => 'active',
                    'last_update_at' => null,
                    'next_update_due_at' => optional($nextDue)->toDateString(),
                    'created_by_name' => 'System Seeder',
                    'notes' => 'Demo seeded enrollment.',
                ],
            );

            $statusOption = ProgramStatusOption::query()
                ->where('program_id', $program->id)
                ->orderBy('display_order')
                ->first();

            $updateDate = Carbon::now()->subMonths(max(1, $index % 4))->toDateString();

            ProgramUpdateEntry::query()->firstOrCreate(
                [
                    'program_enrollment_id' => $enrollment->id,
                    'update_date' => $updateDate,
                ],
                [
                    'status_option_id' => $statusOption?->id,
                    'update_payload' => ['semester' => '1st Sem', 'note' => 'Demo update entry'],
                    'amount_received' => 10000 + ($index * 500),
                    'remarks' => 'Seeded progress update.',
                    'updated_by_name' => 'System Seeder',
                ],
            );

            if ($nextDue) {
                BeneficiaryNotification::query()->firstOrCreate(
                    [
                        'program_enrollment_id' => $enrollment->id,
                        'notification_type' => 'NEXT_UPDATE_DUE',
                        'due_date' => $nextDue->toDateString(),
                    ],
                    [
                        'title' => 'Beneficiary update due',
                        'message' => "{$entry['last']}, {$entry['first']} requires update for {$program->program_code}",
                        'status' => 'open',
                    ],
                );
            }
        }
    }
}
