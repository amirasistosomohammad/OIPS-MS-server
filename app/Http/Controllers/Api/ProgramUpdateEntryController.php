<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\BeneficiaryNotification;
use App\Models\ProgramEnrollment;
use App\Models\ProgramUpdateEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ProgramUpdateEntryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = ProgramUpdateEntry::query()
            ->with([
                'enrollment.beneficiary:id,beneficiary_no,last_name,first_name',
                'enrollment.program:id,program_code,program_name,update_interval_months',
                'statusOption:id,status_code,status_label',
            ]);

        if ($request->filled('program_enrollment_id')) {
            $query->where('program_enrollment_id', (int) $request->query('program_enrollment_id'));
        }

        if ($request->filled('beneficiary_id')) {
            $beneficiaryId = (int) $request->query('beneficiary_id');
            $query->whereHas('enrollment', function ($q) use ($beneficiaryId): void {
                $q->where('beneficiary_id', $beneficiaryId);
            });
        }

        if ($request->filled('program_id')) {
            $programId = (int) $request->query('program_id');
            $query->whereHas('enrollment', function ($q) use ($programId): void {
                $q->where('program_id', $programId);
            });
        }

        $rows = $query
            ->orderByDesc('update_date')
            ->limit(500)
            ->get()
            ->map(function (ProgramUpdateEntry $row): array {
                return [
                    'id' => $row->id,
                    'program_enrollment_id' => $row->program_enrollment_id,
                    'beneficiary_name' => trim(($row->enrollment?->beneficiary?->last_name ?? '').', '.($row->enrollment?->beneficiary?->first_name ?? '')),
                    'program_name' => $row->enrollment?->program?->program_name,
                    'status_option_id' => $row->status_option_id,
                    'status_label' => $row->statusOption?->status_label,
                    'update_date' => optional($row->update_date)->toDateString(),
                    'amount_received' => $row->amount_received,
                    'remarks' => $row->remarks,
                    'update_payload' => $row->update_payload,
                ];
            });

        return response()->json(['data' => $rows]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'program_enrollment_id' => ['required', 'integer', 'exists:program_enrollments,id'],
            'status_option_id' => ['nullable', 'integer', 'exists:program_status_options,id'],
            'update_date' => ['required', 'date'],
            'amount_received' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'remarks' => ['nullable', 'string'],
            'update_payload' => ['nullable', 'array'],
        ]);

        $row = ProgramUpdateEntry::query()->create([
            ...$data,
            'updated_by_name' => $request->user()?->name,
        ]);

        $this->refreshEnrollmentSchedule((int) $data['program_enrollment_id'], (string) $data['update_date']);
        $this->logAction($request, 'CREATE', $row, null, $row->toArray());

        return response()->json(['data' => $row->fresh(), 'message' => 'Program update saved successfully.'], 201);
    }

    public function destroy(Request $request, ProgramUpdateEntry $programUpdate): JsonResponse
    {
        $oldValues = $programUpdate->toArray();
        $recordId = $programUpdate->id;
        $enrollmentId = $programUpdate->program_enrollment_id;
        $programUpdate->delete();

        ActivityLog::query()->create([
            'user_id' => $request->user()?->id,
            'action_type' => 'DELETE',
            'table_name' => 'program_update_entries',
            'record_id' => $recordId,
            'description' => 'Program Update Entry DELETE operation',
            'old_values' => $oldValues,
            'new_values' => null,
            'action_time' => now(),
        ]);

        $latest = ProgramUpdateEntry::query()->where('program_enrollment_id', $enrollmentId)->orderByDesc('update_date')->first();
        if ($latest) {
            $this->refreshEnrollmentSchedule($enrollmentId, (string) optional($latest->update_date)->toDateString());
        }

        return response()->json(['message' => 'Program update deleted successfully.']);
    }

    private function refreshEnrollmentSchedule(int $enrollmentId, string $lastDate): void
    {
        $enrollment = ProgramEnrollment::query()->with('program:id,update_interval_months,program_name')->findOrFail($enrollmentId);
        $lastUpdate = Carbon::parse($lastDate);
        $nextDue = $enrollment->program?->update_interval_months
            ? $lastUpdate->copy()->addMonths((int) $enrollment->program->update_interval_months)
            : null;

        $enrollment->update([
            'last_update_at' => $lastUpdate->toDateString(),
            'next_update_due_at' => optional($nextDue)->toDateString(),
        ]);

        if ($nextDue) {
            BeneficiaryNotification::query()->create([
                'program_enrollment_id' => $enrollment->id,
                'notification_type' => 'NEXT_UPDATE_DUE',
                'title' => 'Next update schedule created',
                'message' => "{$enrollment->program?->program_name} requires next update on ".$nextDue->toDateString(),
                'due_date' => $nextDue->toDateString(),
                'status' => 'open',
            ]);
        }
    }

    private function logAction(Request $request, string $action, ProgramUpdateEntry $row, ?array $oldValues, ?array $newValues): void
    {
        ActivityLog::query()->create([
            'user_id' => $request->user()?->id,
            'action_type' => $action,
            'table_name' => 'program_update_entries',
            'record_id' => $row->id,
            'description' => "Program Update Entry {$action} operation",
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'action_time' => now(),
        ]);
    }
}
