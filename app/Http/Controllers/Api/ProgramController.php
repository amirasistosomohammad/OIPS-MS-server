<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Program;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProgramController extends Controller
{
    public function index(): JsonResponse
    {
        $programs = Program::query()->orderBy('program_name')->get();

        return response()->json([
            'data' => $programs,
        ]);
    }

    public function show(Program $program): JsonResponse
    {
        return response()->json([
            'data' => $program,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'program_code' => ['required', 'string', 'max:30', 'unique:programs,program_code'],
            'program_name' => ['required', 'string', 'max:255'],
            'program_type' => ['nullable', 'string', 'max:100'],
            'update_mode' => ['nullable', 'string', 'max:100'],
            'update_interval_months' => ['nullable', 'integer', 'min:0', 'max:120'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $program = Program::query()->create($data);
        $this->logAction($request, 'CREATE', $program, null, $program->toArray());

        return response()->json([
            'data' => $program,
            'message' => 'Program created successfully.',
        ], 201);
    }

    public function update(Request $request, Program $program): JsonResponse
    {
        $oldValues = $program->toArray();

        $data = $request->validate([
            'program_code' => ['required', 'string', 'max:30', 'unique:programs,program_code,'.$program->id],
            'program_name' => ['required', 'string', 'max:255'],
            'program_type' => ['nullable', 'string', 'max:100'],
            'update_mode' => ['nullable', 'string', 'max:100'],
            'update_interval_months' => ['nullable', 'integer', 'min:0', 'max:120'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $program->update($data);
        $this->logAction($request, 'UPDATE', $program, $oldValues, $program->fresh()->toArray());

        return response()->json([
            'data' => $program->fresh(),
            'message' => 'Program updated successfully.',
        ]);
    }

    public function destroy(Request $request, Program $program): JsonResponse
    {
        $oldValues = $program->toArray();
        $programId = $program->id;
        $program->delete();

        $this->logAction($request, 'DELETE', $program, $oldValues, null, $programId);

        return response()->json([
            'message' => 'Program deleted successfully.',
        ]);
    }

    private function logAction(Request $request, string $action, Program $program, ?array $oldValues, ?array $newValues, ?int $recordIdOverride = null): void
    {
        ActivityLog::query()->create([
            'user_id' => $request->user()?->id,
            'action_type' => $action,
            'table_name' => 'programs',
            'record_id' => $recordIdOverride ?? $program->id,
            'description' => "Program {$action} operation",
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'action_time' => now(),
        ]);
    }
}
