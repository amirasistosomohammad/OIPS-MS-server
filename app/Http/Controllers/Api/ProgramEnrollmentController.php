<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Program;
use App\Models\ProgramEnrollment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ProgramEnrollmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = ProgramEnrollment::query()
            ->with(['beneficiary:id,beneficiary_no,last_name,first_name', 'program:id,program_code,program_name']);

        if ($request->filled('beneficiary_id')) {
            $query->where('beneficiary_id', (int) $request->query('beneficiary_id'));
        }

        if ($request->filled('program_id')) {
            $query->where('program_id', (int) $request->query('program_id'));
        }

        $rows = $query
            ->orderByDesc('created_at')
            ->get()
            ->map(function (ProgramEnrollment $row): array {
                return [
                    'id' => $row->id,
                    'beneficiary_id' => $row->beneficiary_id,
                    'beneficiary_no' => $row->beneficiary?->beneficiary_no,
                    'beneficiary_name' => trim(($row->beneficiary?->last_name ?? '').', '.($row->beneficiary?->first_name ?? '')),
                    'program_id' => $row->program_id,
                    'program_code' => $row->program?->program_code,
                    'program_name' => $row->program?->program_name,
                    'batch' => $row->batch,
                    'date_enrolled' => optional($row->date_enrolled)->toDateString(),
                    'enrollment_status' => $row->enrollment_status,
                    'last_update_at' => optional($row->last_update_at)->toDateString(),
                    'next_update_due_at' => optional($row->next_update_due_at)->toDateString(),
                    'notes' => $row->notes,
                    'input_payload' => $row->input_payload,
                ];
            });

        return response()->json(['data' => $rows]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'beneficiary_id' => ['required', 'integer', 'exists:beneficiaries,id'],
            'program_id' => ['required', 'integer', 'exists:programs,id'],
            'batch' => ['nullable', 'string', 'max:80'],
            'date_enrolled' => ['nullable', 'date'],
            'enrollment_status' => ['nullable', 'string', 'max:40'],
            'notes' => ['nullable', 'string'],
            'input_payload' => ['nullable', 'array'],
        ]);

        $program = Program::query()->findOrFail((int) $data['program_id']);
        $dateEnrolled = ! empty($data['date_enrolled']) ? Carbon::parse($data['date_enrolled']) : now();
        $nextDueAt = $program->update_interval_months ? $dateEnrolled->copy()->addMonths((int) $program->update_interval_months) : null;

        $row = ProgramEnrollment::query()->create([
            ...$data,
            'date_enrolled' => $data['date_enrolled'] ?? $dateEnrolled->toDateString(),
            'enrollment_status' => $data['enrollment_status'] ?? 'active',
            'next_update_due_at' => optional($nextDueAt)->toDateString(),
            'created_by_name' => $request->user()?->name,
        ]);

        $this->logAction($request, 'CREATE', $row, null, $row->toArray());
        $fresh = ProgramEnrollment::query()->with(['beneficiary:id,beneficiary_no,last_name,first_name', 'program:id,program_code,program_name'])->findOrFail($row->id);

        return response()->json(['data' => $fresh, 'message' => 'Enrollment created successfully.'], 201);
    }

    public function show(ProgramEnrollment $enrollment): JsonResponse
    {
        $enrollment->load(['beneficiary:id,beneficiary_no,last_name,first_name', 'program:id,program_code,program_name']);

        return response()->json(['data' => $enrollment]);
    }

    public function update(Request $request, ProgramEnrollment $enrollment): JsonResponse
    {
        $oldValues = $enrollment->toArray();
        $data = $request->validate([
            'beneficiary_id' => ['required', 'integer', 'exists:beneficiaries,id'],
            'program_id' => ['required', 'integer', 'exists:programs,id'],
            'batch' => ['nullable', 'string', 'max:80'],
            'date_enrolled' => ['nullable', 'date'],
            'enrollment_status' => ['nullable', 'string', 'max:40'],
            'notes' => ['nullable', 'string'],
            'input_payload' => ['nullable', 'array'],
        ]);

        $program = Program::query()->findOrFail((int) $data['program_id']);
        $dateEnrolled = ! empty($data['date_enrolled']) ? Carbon::parse($data['date_enrolled']) : ($enrollment->date_enrolled ?? now());
        $nextDueAt = $program->update_interval_months ? $dateEnrolled->copy()->addMonths((int) $program->update_interval_months) : null;

        $enrollment->update([
            ...$data,
            'next_update_due_at' => optional($nextDueAt)->toDateString(),
        ]);

        $fresh = $enrollment->fresh();
        $this->logAction($request, 'UPDATE', $fresh, $oldValues, $fresh->toArray());

        return response()->json(['data' => $fresh, 'message' => 'Enrollment updated successfully.']);
    }

    public function destroy(Request $request, ProgramEnrollment $enrollment): JsonResponse
    {
        $oldValues = $enrollment->toArray();
        $rowId = $enrollment->id;
        $enrollment->delete();

        ActivityLog::query()->create([
            'user_id' => $request->user()?->id,
            'action_type' => 'DELETE',
            'table_name' => 'program_enrollments',
            'record_id' => $rowId,
            'description' => 'Program Enrollment DELETE operation',
            'old_values' => $oldValues,
            'new_values' => null,
            'action_time' => now(),
        ]);

        return response()->json(['message' => 'Enrollment deleted successfully.']);
    }

    private function logAction(Request $request, string $action, ProgramEnrollment $row, ?array $oldValues, ?array $newValues): void
    {
        ActivityLog::query()->create([
            'user_id' => $request->user()?->id,
            'action_type' => $action,
            'table_name' => 'program_enrollments',
            'record_id' => $row->id,
            'description' => "Program Enrollment {$action} operation",
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'action_time' => now(),
        ]);
    }
}
